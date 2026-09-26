// Gabungkan bench/results/{blade,react}.json menjadi tabel markdown (bench/results/RESULTS.md).
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIR = join(dirname(fileURLToPath(import.meta.url)), 'results');
const load = (f) => (existsSync(join(DIR, f + '.json')) ? JSON.parse(readFileSync(join(DIR, f + '.json'), 'utf8')) : null);
const b = load('blade');
const r = load('react');
if (!b || !r) {
    console.error('Butuh bench/results/blade.json dan react.json.');
    process.exit(1);
}

const ROWS = [
    ['Muat dingin listing (HP)', 'coldListing', [['ttfb', 'TTFB', 'ms'], ['fcp', 'FCP', 'ms'], ['lcp', 'LCP', 'ms'], ['tbt', 'Total Blocking Time', 'ms'], ['load', 'load event', 'ms'], ['kbHtml', 'HTML', 'KB'], ['kbJs', 'JavaScript', 'KB'], ['kbCss', 'CSS', 'KB'], ['kbTotal', 'Total transfer', 'KB']]],
    ['Muat dingin detail (HP)', 'coldDetail', [['ttfb', 'TTFB', 'ms'], ['fcp', 'FCP', 'ms'], ['lcp', 'LCP', 'ms'], ['tbt', 'Total Blocking Time', 'ms'], ['kbJs', 'JavaScript', 'KB'], ['kbTotal', 'Total transfer', 'KB']]],
    ['Interaktivitas', 'saveListing', [['saveInteractive', '♡ pertama merespons (dari awal navigasi)', 'ms']]],
    ['Navigasi di dalam situs', 'navigation', [['navDetail', 'Listing → detail', 'ms'], ['navBack', 'Kembali ke listing', 'ms'], ['kbNav', 'Transfer saat pindah', 'KB']]],
    ['Filter di HP', 'mobileFilter', [['sheetOpen', 'Buka sheet filter', 'ms'], ['sheetCount', 'Ketuk chip → hitungan baru', 'ms'], ['sheetApply', 'Terapkan filter', 'ms'], ['sortChange', 'Ganti urutan', 'ms']]],
    ['Tersimpan', 'saveAndCompare', [['openSaved', 'Buka /tersimpan sampai tabel tampil', 'ms']]],
];

const fmt = (v, unit) => (v == null ? '—' : unit === 'KB' ? v.toLocaleString('id-ID') + ' KB' : v.toLocaleString('id-ID') + ' ms');
const diff = (bv, rv) => {
    if (bv == null || rv == null || bv === 0) return '';
    const pct = Math.round(((rv - bv) / bv) * 100);
    return pct === 0 ? '≈' : (pct > 0 ? '+' : '') + pct + '%';
};

let md = `# Hasil benchmark: Blade vs React\n\n`;
md += `- Tanggal: ${b.meta.date.slice(0, 10)} · ${b.meta.runs} run per skenario, nilai median (p75 dalam kurung)\n`;
md += `- Perangkat: ${b.meta.device}\n- Jaringan: ${b.meta.network}\n- Server: ${b.meta.server}\n- Catatan: ${b.meta.fonts}\n\n`;
md += `Kolom "React vs Blade": positif berarti React lebih lambat atau lebih besar.\n\n`;
md += `## Server (tanpa throttling)\n\n| | Blade | React | React vs Blade |\n|---|---|---|---|\n`;
md += `| Render listing | ${fmt(b.server.listingMs, 'ms')} | ${fmt(r.server.listingMs, 'ms')} | ${diff(b.server.listingMs, r.server.listingMs)} |\n`;
md += `| Render detail | ${fmt(b.server.detailMs, 'ms')} | ${fmt(r.server.detailMs, 'ms')} | ${diff(b.server.detailMs, r.server.detailMs)} |\n`;
for (const [title, key, metrics] of ROWS) {
    md += `\n## ${title}\n\n| | Blade | React | React vs Blade |\n|---|---|---|---|\n`;
    for (const [m, label, unit] of metrics) {
        const bv = b[key][m], rv = r[key][m];
        md += `| ${label} | ${fmt(bv?.median, unit)} (${fmt(bv?.p75, unit)}) | ${fmt(rv?.median, unit)} (${fmt(rv?.p75, unit)}) | ${diff(bv?.median, rv?.median)} |\n`;
    }
}
writeFileSync(join(DIR, 'RESULTS.md'), md);
console.log(md);
