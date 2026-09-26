// Ubah bench/results/blade.json menjadi tabel markdown (bench/results/RESULTS.md).
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const DIR = join(dirname(fileURLToPath(import.meta.url)), 'results');
const file = join(DIR, 'blade.json');
if (!existsSync(file)) {
    console.error('Belum ada bench/results/blade.json. Jalankan node bench/run.mjs dulu.');
    process.exit(1);
}
const b = JSON.parse(readFileSync(file, 'utf8'));

const ROWS = [
    ['Muat dingin listing (HP)', 'coldListing', [['ttfb', 'TTFB', 'ms'], ['fcp', 'FCP', 'ms'], ['lcp', 'LCP', 'ms'], ['tbt', 'Total Blocking Time', 'ms'], ['load', 'load event', 'ms'], ['kbHtml', 'HTML', 'KB'], ['kbJs', 'JavaScript', 'KB'], ['kbCss', 'CSS', 'KB'], ['kbTotal', 'Total transfer', 'KB']]],
    ['Muat dingin detail (HP)', 'coldDetail', [['ttfb', 'TTFB', 'ms'], ['fcp', 'FCP', 'ms'], ['lcp', 'LCP', 'ms'], ['tbt', 'Total Blocking Time', 'ms'], ['kbJs', 'JavaScript', 'KB'], ['kbTotal', 'Total transfer', 'KB']]],
    ['Interaktivitas', 'saveListing', [['saveInteractive', '♡ pertama merespons (dari awal navigasi)', 'ms']]],
    ['Navigasi di dalam situs', 'navigation', [['navDetail', 'Listing → detail', 'ms'], ['navBack', 'Kembali ke listing', 'ms'], ['kbNav', 'Transfer saat pindah', 'KB']]],
    ['Filter di HP', 'mobileFilter', [['sheetOpen', 'Buka sheet filter', 'ms'], ['sheetCount', 'Ketuk chip → hitungan baru', 'ms'], ['sheetApply', 'Terapkan filter', 'ms'], ['sortChange', 'Ganti urutan', 'ms']]],
    ['Tersimpan', 'saveAndCompare', [['openSaved', 'Buka /tersimpan sampai tabel tampil', 'ms']]],
];

const fmt = (v, unit) => (v == null ? '—' : v.toLocaleString('id-ID') + ' ' + unit);

let md = `# Hasil benchmark\n\n`;
md += `- Tanggal: ${b.meta.date.slice(0, 10)} · ${b.meta.runs} run per skenario, nilai median dan p75\n`;
md += `- Perangkat: ${b.meta.device}\n- Jaringan: ${b.meta.network}\n- Server: ${b.meta.server}\n- Catatan: ${b.meta.fonts}\n`;
md += `\nPerbandingan dengan React: [docs/decisions/2026-09-frontend-bench](../../docs/decisions/2026-09-frontend-bench/README.md).\n`;
md += `\n## Server (tanpa throttling)\n\n| | median |\n|---|---|\n`;
md += `| Render listing | ${fmt(b.server.listingMs, 'ms')} |\n| Render detail | ${fmt(b.server.detailMs, 'ms')} |\n`;
for (const [title, key, metrics] of ROWS) {
    md += `\n## ${title}\n\n| | median | p75 |\n|---|---|---|\n`;
    for (const [m, label, unit] of metrics) md += `| ${label} | ${fmt(b[key][m]?.median, unit)} | ${fmt(b[key][m]?.p75, unit)} |\n`;
}
writeFileSync(join(DIR, 'RESULTS.md'), md);
console.log(md);
