// Smoke test browser: alur utama di desktop dan HP.
// Pakai: BASE=http://127.0.0.1:8000 node tests/browser/smoke.mjs
import { chromium } from 'playwright';

const BASE = process.env.BASE || 'http://127.0.0.1:8000';
let failures = 0;
const ok = (cond, label, extra = '') => {
    console.log(`${cond ? 'ok  ' : 'FAIL'} ${label}${extra ? ' — ' + extra : ''}`);
    if (!cond) failures++;
};

const browser = await chromium.launch();
const errors = [];
const track = (page) => {
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => { if (m.type() === 'error' && !/fonts\.g/.test(m.text()) && !/ERR_FAILED/.test(m.text())) errors.push(m.text()); });
    return page.route(/fonts\.(googleapis|gstatic)\.com/, (r) => r.abort());
};

const p = await browser.newPage({ viewport: { width: 1280, height: 900 } });
await track(p);
const h1 = async () => (await p.locator('h1').first().textContent()).trim();

// 1. rute
const routes = {
    '/': 'Vendor acara di Jabodetabek',
    '/wedding': 'Vendor pernikahan',
    '/corporate/eo/tangerang': 'EO acara kantor di Tangerang',
    '/wedding/venue/jakarta-selatan': 'Venue pernikahan di Jakarta Selatan',
    '/wedding/venue/ballroom-kebayoran': 'Ballroom Kebayoran',
    '/wedding/bundle/paket-intimate-wedding-150-pax': 'Paket Intimate Wedding 150 pax',
    '/koleksi/rooftop-jaksel-dibawah-50jt': 'rooftop di Jakarta Selatan',
    '/tersimpan': 'Yang kamu simpan',
    '/kasih-tau-kami': 'Ngga nemu',
};
for (const [path, text] of Object.entries(routes)) {
    const res = await p.goto(BASE + path);
    ok(res.status() === 200 && (await h1()).includes(text), `rute ${path}`);
}
const nf = await p.goto(BASE + '/tidak-ada');
ok(nf.status() === 404 && (await h1()).includes('tidak ada'), 'rute 404');
errors.length = 0; // log 404 yang disengaja

// 2. filter desktop
await p.goto(BASE + '/wedding/venue/jakarta-selatan');
await p.locator('.filterbar label', { hasText: 'Indoor / outdoor' }).locator('select').selectOption('outdoor');
await p.waitForURL(/tipe=outdoor/);
await p.locator('.chip-x', { hasText: 'Outdoor' }).waitFor();
ok(/tipe=outdoor/.test(p.url()), 'filter memperbarui URL', p.url());
ok(await p.locator('.chip-x', { hasText: 'Outdoor' }).count() === 1, 'chip filter aktif tampil');
await p.locator('.chip-x', { hasText: 'Outdoor' }).click();
await p.waitForURL((u) => !/tipe=/.test(u.toString()));
ok(!/tipe=/.test(p.url()), 'chip ✕ menghapus filter');

// 3. muat lebih banyak
const n0 = await p.locator('.card').count();
await p.locator('.more').click();
await p.waitForFunction((n) => document.querySelectorAll('.card').length > n, n0);
ok((await p.locator('.card').count()) > n0, 'muat lebih banyak', `${n0} → ${await p.locator('.card').count()}`);

// 4. simpan + navigasi
await p.locator('.card .save').nth(0).click();
await p.locator('.card .save').nth(2).click();
ok((await p.locator('.nav-links').textContent()).includes('(2)'), 'hitungan ♡ di nav');
await p.locator('.card .card-link').first().click();
await p.waitForURL(/ballroom-kebayoran/);
ok((await p.locator('.nav-links').textContent()).includes('(2)'), 'hitungan ♡ bertahan setelah navigasi');
ok((await p.locator('.sticky-box .save').getAttribute('aria-pressed')) === 'true', 'status simpan di detail');
await p.locator('.nav-links a', { hasText: 'Tersimpan' }).click();
await p.waitForURL(/tersimpan/);
await p.waitForSelector('table.cmp thead th:nth-child(3)');
ok((await p.locator('table.cmp thead th').count()) === 3, 'tabel banding 2 kolom');
await p.locator('table.cmp textarea').first().fill('tanya DP');
await p.reload();
await p.waitForSelector('table.cmp textarea');
ok((await p.locator('table.cmp textarea').first().inputValue()) === 'tanya DP', 'catatan tersimpan');

// 4b. bagikan (Chromium headless tanpa navigator.share → popover)
await p.context().grantPermissions(['clipboard-read', 'clipboard-write'], { origin: BASE });
await p.goto(BASE + '/wedding/venue/ballroom-kebayoran');
await p.locator('.share-btn').click();
await p.locator('.share-pop').waitFor({ state: 'visible' });
ok(await p.locator('.share-pop').isVisible(), 'popover bagikan terbuka');
ok(/^https:\/\/wa\.me\/\?text=/.test(await p.locator('.share-pop a', { hasText: 'WhatsApp' }).getAttribute('href')), 'bagikan ke WhatsApp');
await p.locator('.share-pop button', { hasText: 'Salin tautan' }).click();
await p.locator('.share-pop button', { hasText: 'Tersalin' }).waitFor();
const copied = await p.evaluate(() => navigator.clipboard.readText());
ok(copied === BASE + '/wedding/venue/ballroom-kebayoran', 'salin tautan', copied);
await p.keyboard.press('Escape');
await p.locator('.share-pop').waitFor({ state: 'hidden' });
ok(!(await p.locator('.share-pop').isVisible()), 'Escape menutup popover');

await p.goto(BASE + '/wedding/bundle/paket-intimate-wedding-150-pax');
await p.locator('.share-btn').click();
await p.locator('.share-pop').waitFor({ state: 'visible' });
ok((await p.locator('.share-url').inputValue()) === BASE + '/wedding/bundle/paket-intimate-wedding-150-pax', 'bagikan di halaman bundle');

// 4c. HP dengan sheet bawaan: navigator.share dipakai, popover tidak muncul
const ns = await browser.newPage();
await track(ns);
await ns.addInitScript(() => { navigator.share = async (d) => { window.__shared = d; }; });
await ns.goto(BASE + '/wedding/venue/ballroom-kebayoran');
await ns.locator('.share-btn').click();
await ns.waitForFunction(() => window.__shared);
const shared = await ns.evaluate(() => window.__shared);
ok(shared.url === BASE + '/wedding/venue/ballroom-kebayoran' && shared.title === 'Ballroom Kebayoran', 'navigator.share menerima judul dan URL');
ok(!(await ns.locator('.share-pop').isVisible()), 'popover tidak muncul bila ada sheet bawaan');
await ns.close();

// 5. popover kategori
await p.goto(BASE + '/');
await p.locator('.cat-pop button').first().click();
await p.locator('.cat-pop.open a', { hasText: 'Corporate' }).click();
await p.waitForURL(/\/corporate\/venue$/);
ok(p.url().endsWith('/corporate/venue'), 'popover kategori → corporate');

// 6. hasil kosong
await p.goto(BASE + '/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor');
ok((await p.locator('.msg-title').textContent()).includes('Belum ada'), 'pesan hasil kosong');
ok((await p.locator('.sugg').count()) >= 1, 'relaksasi tampil', (await p.locator('.sugg').first().textContent()).replace(/\s+/g, ' ').trim());
ok((await p.locator('#zf-tamu').inputValue()) === '500+', 'form terisi dari filter');

// 7. form
await p.goto(BASE + '/kasih-tau-kami?jenis=wedding');
await p.locator('button[type=submit]').click();
await p.waitForSelector('.form-error');
ok((await p.locator('.form-error').textContent()).includes('WhatsApp'), 'validasi nomor WA');
await p.locator('#df-wa').fill('0812 3456 7890');
await p.locator('button[type=submit]').click();
await p.waitForURL(/terkirim/);
ok((await h1()).includes('sudah kami terima') && (await p.locator('.lead').textContent()).includes('081234567890'), 'form terkirim');

// 8. mobile
const m = await browser.newPage({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
await track(m);
await m.goto(BASE + '/wedding/venue/jakarta-selatan');
await m.locator('.mbar-btn').click();
await m.waitForSelector('.sheet');
await m.locator('.sheet .chip', { hasText: 'Outdoor' }).click();
await m.waitForFunction(() => /Tampilkan \d+ hasil/.test(document.querySelector('.sheet-apply')?.textContent || '') && !/16/.test(document.querySelector('.sheet-apply').textContent));
ok(/Tampilkan \d+ hasil/.test(await m.locator('.sheet-apply').textContent()), 'sheet menghitung hasil', (await m.locator('.sheet-apply').textContent()).trim());
await m.locator('.sheet-apply').click();
await m.waitForURL(/tipe=outdoor/);
ok(/tipe=outdoor/.test(m.url()), 'sheet menerapkan filter');
await m.goto(BASE + '/wedding/venue/ballroom-kebayoran');
ok(await m.locator('.m-cta').isVisible(), 'bar CTA bawah di detail');
await m.locator('.share-btn').click();
await m.locator('.share-pop').waitFor({ state: 'visible' });
const box = await m.locator('.share-pop').boundingBox();
ok(box.x >= 0 && box.x + box.width <= 390, 'popover bagikan muat di layar HP', `${Math.round(box.x)}–${Math.round(box.x + box.width)}px`);
ok((await m.evaluate(() => document.documentElement.scrollWidth)) <= 390, 'tanpa scroll samping di 390px');

ok(errors.length === 0, 'tanpa error JS', errors.join(' | '));
await browser.close();
console.log(failures ? `\n${failures} gagal` : '\nsemua lolos');
process.exit(failures ? 1 : 0);
