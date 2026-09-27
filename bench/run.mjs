// Benchmark performa situs publik (Blade + Livewire + Alpine).
//
//   npm run build                       # sekali, sebelum benchmark
//   node bench/run.mjs                  # 10 run per skenario
//   node bench/run.mjs --runs=5
//
// Situs dijalankan dalam mode produksi (config/route/view cache, opcache), lalu diukur
// dengan Chromium yang meniru HP kelas menengah: CPU 4× lebih lambat dan jaringan
// "slow 4G" gaya Lighthouse. Perbandingan Blade vs React yang dulu memakai harness ini:
// docs/decisions/2026-09-frontend-bench/.
import { execSync, spawn } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import http from 'node:http';
import { dirname, join } from 'node:path';
import zlib from 'node:zlib';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const PORT = 8200; // proxy kompresi, yang diakses browser
const PHP_PORT = 8201; // server PHP di belakangnya
const BASE = `http://127.0.0.1:${PORT}`;
const ORIGIN = `http://127.0.0.1:${PHP_PORT}`;
const args = Object.fromEntries(process.argv.slice(2).map((a) => a.replace(/^--/, '').split('=')));
const RUNS = Number(args.runs || 10);

const LISTING = '/wedding/venue/jakarta-selatan';
const DETAIL = '/wedding/venue/ballroom-kebayoran';

// Profil perangkat: mirip Moto G Power, throttling seperti Lighthouse mobile.
const DEVICE = {
    viewport: { width: 412, height: 823 },
    deviceScaleFactor: 2.625,
    isMobile: true,
    hasTouch: true,
    userAgent: 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Mobile Safari/537.36',
};
const CPU_SLOWDOWN = 4;
const NETWORK = { offline: false, latency: 150, downloadThroughput: (1638.4 * 1024) / 8, uploadThroughput: (675 * 1024) / 8 };

/* ---------------- util ---------------- */
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const median = (xs) => {
    const s = xs.filter((x) => x != null).sort((a, b) => a - b);
    if (!s.length) return null;
    const m = Math.floor(s.length / 2);
    return s.length % 2 ? s[m] : (s[m - 1] + s[m]) / 2;
};
const p75 = (xs) => {
    const s = xs.filter((x) => x != null).sort((a, b) => a - b);
    return s.length ? s[Math.min(s.length - 1, Math.ceil(s.length * 0.75) - 1)] : null;
};
const round = (x) => (x == null ? null : Math.round(x));
function summarize(runs) {
    const keys = [...new Set(runs.flatMap((r) => Object.keys(r)))];
    return Object.fromEntries(keys.map((k) => [k, { median: round(median(runs.map((r) => r[k]))), p75: round(p75(runs.map((r) => r[k]))) }]));
}
function sh(cmd, env) {
    execSync(cmd, { cwd: ROOT, env, stdio: 'pipe' });
}
async function waitFor(url, tries = 100) {
    for (let i = 0; i < tries; i++) {
        try {
            const r = await fetch(url);
            if (r.status < 500) return;
        } catch {
            /* belum siap */
        }
        await sleep(100);
    }
    throw new Error('Server tidak siap: ' + url);
}

/* ---------------- server ---------------- */

// php -S tidak mengompres respons. Produksi selalu memakai gzip/brotli (nginx atau CDN),
// jadi proxy kecil ini mengompres teks dengan brotli q5.
function startProxy() {
    const server = http.createServer((req, res) => {
        const up = http.request({ host: '127.0.0.1', port: PHP_PORT, path: req.url, method: req.method, headers: req.headers }, (ur) => {
            const headers = { ...ur.headers };
            const compressible = /text\/|javascript|json|css|svg/.test(headers['content-type'] || '');
            if (compressible && /\bbr\b/.test(req.headers['accept-encoding'] || '') && ur.statusCode !== 204 && ur.statusCode !== 304) {
                delete headers['content-length'];
                headers['content-encoding'] = 'br';
                headers.vary = headers.vary ? headers.vary + ', Accept-Encoding' : 'Accept-Encoding';
                res.writeHead(ur.statusCode, headers);
                ur.pipe(zlib.createBrotliCompress({ params: { [zlib.constants.BROTLI_PARAM_QUALITY]: 5 } })).pipe(res);
            } else {
                res.writeHead(ur.statusCode, headers);
                ur.pipe(res);
            }
        });
        up.on('error', () => {
            res.writeHead(502);
            res.end();
        });
        req.pipe(up);
    });
    return new Promise((r) => server.listen(PORT, '127.0.0.1', () => r(server)));
}

async function startServers() {
    const env = { ...process.env, APP_ENV: 'production', APP_DEBUG: 'false', LOG_LEVEL: 'error', PHP_CLI_SERVER_WORKERS: '4' };
    sh('php artisan optimize:clear', env);
    sh('php artisan config:cache && php artisan route:cache && php artisan view:cache', env);
    const procs = [];
    procs.push(
        spawn('php', ['-d', 'opcache.enable_cli=1', '-d', 'opcache.validate_timestamps=0', '-S', `127.0.0.1:${PHP_PORT}`, join(ROOT, 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')], {
            cwd: join(ROOT, 'public'),
            env,
            stdio: 'ignore',
        }),
    );
    await waitFor(ORIGIN + '/');
    const proxy = await startProxy();
    return {
        env,
        stop: () => {
            proxy.close();
            procs.forEach((p) => p.kill('SIGTERM'));
        },
    };
}

/* ---------------- browser ---------------- */
const INIT = () => {
    window.__m = { lt: [], lcp: null };
    try {
        new PerformanceObserver((l) => l.getEntries().forEach((e) => window.__m.lt.push([e.startTime, e.duration]))).observe({ type: 'longtask', buffered: true });
        new PerformanceObserver((l) => {
            const es = l.getEntries();
            window.__m.lcp = es[es.length - 1].startTime;
        }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch {
        /* abaikan */
    }
};

async function newPage(browser, { cold = true } = {}) {
    const context = await browser.newContext(DEVICE);
    await context.route(/fonts\.(googleapis|gstatic)\.com/, (r) => r.abort()); // font eksternal diblokir untuk keduanya
    await context.addInitScript(INIT);
    const page = await context.newPage();
    const cdp = await context.newCDPSession(page);
    await cdp.send('Network.enable');
    await cdp.send('Network.emulateNetworkConditions', NETWORK);
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: CPU_SLOWDOWN });
    if (cold) await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });

    const bytes = { html: 0, js: 0, css: 0, data: 0 };
    const types = new Map();
    cdp.on('Network.responseReceived', (e) => types.set(e.requestId, { type: e.type, url: e.response.url }));
    cdp.on('Network.loadingFinished', (e) => {
        const t = types.get(e.requestId);
        if (!t || !t.url.startsWith(BASE)) return;
        const k = t.type === 'Document' ? 'html' : t.type === 'Script' ? 'js' : t.type === 'Stylesheet' ? 'css' : 'data';
        bytes[k] += e.encodedDataLength;
    });
    const resetBytes = () => Object.keys(bytes).forEach((k) => (bytes[k] = 0));
    return { context, page, bytes, resetBytes };
}

async function settle(page) {
    await page.waitForLoadState('load');
    await page.waitForLoadState('networkidle').catch(() => {});
    await sleep(800);
}

/** Muat dingin: TTFB, FCP, LCP, TBT, byte terkirim. */
async function coldLoad(browser, path) {
    const { context, page, bytes } = await newPage(browser);
    await page.goto(BASE + path, { waitUntil: 'load' });
    await settle(page);
    const m = await page.evaluate(() => {
        const nav = performance.getEntriesByType('navigation')[0];
        const fcp = performance.getEntriesByName('first-contentful-paint')[0]?.startTime ?? null;
        const tbt = window.__m.lt.filter(([s]) => fcp == null || s >= fcp).reduce((sum, [, d]) => sum + Math.max(0, d - 50), 0);
        return { ttfb: nav.responseStart, fcp, lcp: window.__m.lcp, tbt, domContentLoaded: nav.domContentLoadedEventEnd, load: nav.loadEventEnd };
    });
    await context.close();
    return { ...m, kbHtml: bytes.html / 1024, kbJs: bytes.js / 1024, kbCss: bytes.css / 1024, kbTotal: (bytes.html + bytes.js + bytes.css + bytes.data) / 1024 };
}

/** Kapan tombol ♡ pertama kali benar-benar merespons ketukan, diukur dari awal navigasi. */
async function timeToSave(browser, path) {
    const { context, page } = await newPage(browser);
    const t0 = Date.now();
    await page.goto(BASE + path, { waitUntil: 'commit' });
    const btn = page.locator('.card .save').first();
    await btn.waitFor({ state: 'attached', timeout: 30000 });
    let t = null;
    while (Date.now() - t0 < 30000) {
        await btn.dispatchEvent('click').catch(() => {});
        if ((await btn.getAttribute('aria-pressed').catch(() => null)) === 'true') {
            t = Date.now() - t0;
            break;
        }
        await sleep(25);
    }
    await context.close();
    return { saveInteractive: t };
}

/** Pindah halaman di dalam situs: listing → detail (aset sudah di cache). */
async function navigation(browser) {
    const { context, page, bytes, resetBytes } = await newPage(browser, { cold: false });
    await page.goto(BASE + LISTING, { waitUntil: 'load' });
    await settle(page);
    resetBytes();
    const t0 = Date.now();
    await page.locator('.card .card-link').first().click();
    await page.waitForFunction(() => /Ballroom Kebayoran/.test(document.querySelector('h1')?.textContent || '') && location.pathname.endsWith('/ballroom-kebayoran'));
    const navDetail = Date.now() - t0;
    await settle(page);
    const kbNav = (bytes.html + bytes.js + bytes.css + bytes.data) / 1024;

    resetBytes();
    const t1 = Date.now();
    await page.goBack();
    await page.waitForFunction(() => /Venue pernikahan di Jakarta Selatan/.test(document.querySelector('h1')?.textContent || ''));
    const navBack = Date.now() - t1;
    await context.close();
    return { navDetail, navBack, kbNav };
}

/** Filter di HP: buka sheet, pilih Outdoor (hitungan diperbarui), terapkan. */
async function mobileFilter(browser) {
    const { context, page } = await newPage(browser, { cold: false });
    await page.goto(BASE + LISTING, { waitUntil: 'load' });
    await settle(page);

    let t = Date.now();
    await page.locator('.mfbar-btn').click();
    await page.locator('.sheet').waitFor();
    const sheetOpen = Date.now() - t;

    t = Date.now();
    await page.locator('.sheet .chip', { hasText: 'Outdoor' }).click();
    await page.waitForFunction(() => /Tampilkan 10 hasil/.test(document.querySelector('.sheet-apply')?.textContent || ''));
    const sheetCount = Date.now() - t;

    t = Date.now();
    await page.locator('.sheet-apply').click();
    await page.waitForFunction(() => /tipe=outdoor/.test(location.search) && !document.querySelector('.sheet') && /\(2\)/.test(document.querySelector('.mfbar-btn')?.textContent || ''));
    const sheetApply = Date.now() - t;

    t = Date.now();
    await page.locator('.mfbar select').selectOption('harga');
    await page.waitForFunction(() => /urut=harga/.test(location.search));
    await page.waitForFunction(() => document.querySelector('.card .nm')?.textContent.trim() === 'Kebun Jagakarsa');
    const sortChange = Date.now() - t;

    await context.close();
    return { sheetOpen, sheetCount, sheetApply, sortChange };
}

/** Simpan lalu buka halaman tersimpan (tabel dari /tersimpan/data). */
async function saveAndCompare(browser) {
    const { context, page } = await newPage(browser, { cold: false });
    await page.goto(BASE + LISTING, { waitUntil: 'load' });
    await settle(page);
    await page.locator('.card .save').nth(1).click();
    await page.locator('.card .save').nth(2).click();
    const t = Date.now();
    await page.locator('.wishlist').click();
    await page.waitForSelector('table.cmp thead th:nth-child(3)');
    const openSaved = Date.now() - t;
    await context.close();
    return { openSaved };
}

/** Waktu render server tanpa throttling. */
async function serverTime(path, n = 40) {
    const xs = [];
    for (let i = 0; i < n; i++) {
        const t = performance.now();
        const r = await fetch(ORIGIN + path);
        await r.text();
        xs.push(performance.now() - t);
    }
    return median(xs);
}

/* ---------------- main ---------------- */
if (!existsSync(join(ROOT, 'public/build/manifest.json'))) {
    console.error('Jalankan `npm run build` dulu.');
    process.exit(1);
}

let results = null;
const browser = await chromium.launch();
try {
    const server = await startServers();
    try {
        for (const p of [LISTING, DETAIL]) for (let i = 0; i < 5; i++) await (await fetch(BASE + p)).text(); // pemanasan

        const server_ = { listingMs: round(await serverTime(LISTING)), detailMs: round(await serverTime(DETAIL)) };
        console.log('server', server_);

        const scenarios = {
            coldListing: () => coldLoad(browser, LISTING),
            coldDetail: () => coldLoad(browser, DETAIL),
            saveListing: () => timeToSave(browser, LISTING),
            navigation: () => navigation(browser),
            mobileFilter: () => mobileFilter(browser),
            saveAndCompare: () => saveAndCompare(browser),
        };
        results = { server: server_ };
        for (const [name, fn] of Object.entries(scenarios)) {
            const runs = [];
            for (let i = 0; i < RUNS; i++) runs.push(await fn());
            results[name] = summarize(runs);
            console.log(name, Object.fromEntries(Object.entries(results[name]).map(([k, v]) => [k, v.median])));
        }
    } finally {
        server.stop();
        await sleep(500);
    }
} finally {
    await browser.close();
    sh('php artisan optimize:clear', process.env);
}

const meta = {
    date: new Date().toISOString(),
    runs: RUNS,
    device: `${DEVICE.viewport.width}×${DEVICE.viewport.height} @${DEVICE.deviceScaleFactor}x, CPU ${CPU_SLOWDOWN}× lebih lambat`,
    network: `RTT ${NETWORK.latency} ms, turun ${Math.round((NETWORK.downloadThroughput * 8) / 1024)} Kbps, naik ${Math.round((NETWORK.uploadThroughput * 8) / 1024)} Kbps`,
    server: 'php -S (4 worker) + opcache, config/route/view cache, di belakang proxy brotli q5',
    fonts: 'font Google diblokir',
};
mkdirSync(join(ROOT, 'bench/results'), { recursive: true });
writeFileSync(join(ROOT, 'bench/results/blade.json'), JSON.stringify({ meta, ...results }, null, 2) + '\n');
console.log('\nHasil ditulis ke bench/results/. Buat tabel: node bench/report.mjs');
