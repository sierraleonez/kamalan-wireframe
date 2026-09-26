# EventHub Jabodetabek — bake-off frontend

Katalog vendor acara Jabodetabek. Repo ini sedang dipakai untuk **memilih stack frontend**:
situs publik yang sama dibangun dua kali di atas satu aplikasi Laravel, lalu dibandingkan.

| Frontend | Status | Isi |
| --- | --- | --- |
| `blade` | selesai | Blade + Livewire 3 (listing, filter, sheet) + Alpine (♡ tersimpan, popover, catatan) + `wire:navigate` |
| `react` | selesai | Inertia 2 + React 19 + TypeScript, SSR lewat Node |

Hasil perbandingan: [`bench/results/RESULTS.md`](bench/results/RESULTS.md).

Backend (database, Filament, cache, pencatatan klik) sengaja belum dibuat. Data dibaca dari
`resources/data/catalog.json`, hasil ekspor prototipe.

## Menjalankan

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
npm run build
php artisan serve          # http://127.0.0.1:8000
```

Pilih frontend lewat `FRONTEND=blade|react` di `.env`. Tidak perlu database.
Untuk React, jalankan juga server SSR: `php artisan inertia:start-ssr`.

## Rute

Sama dengan prototipe: `/`, `/wedding`, `/corporate`, `/{jenis}/{kategori}[/{area}]`,
`/{jenis}/{kategori}/{vendor}`, `/{jenis}/bundle[/{paket}]`, `/bundle`, `/koleksi[/{slug}]`,
`/tersimpan`, `/kasih-tau-kami`. Filter listing ada di query string, mis.
`/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor` (hasil kosong).

Pendukung: `/go/{ref}` meneruskan ke wa.me, `/tersimpan/data?keys=` (JSON tabel banding),
`/{jenis}/{kategori}/hitung` (JSON jumlah hasil).

## Struktur

```
app/Catalog/          data + logika bersama kedua frontend
  Catalog.php         baca catalog.json (di-cache sebagai PHP di bootstrap/cache)
  Filters.php         definisi filter per jenis × kategori
  Pages.php           props siap-tampil per halaman (dipakai Blade dan React)
  Present.php, Copy.php  format, kartu, tulisan, catatan pena
app/Support/Page.php  render Blade atau Inertia sesuai FRONTEND
app/Livewire/Listing.php + resources/views/livewire/listing.blade.php
resources/views/      layout, komponen, halaman Blade
resources/css/site.css  gaya wireframe, dipakai kedua frontend
resources/js/blade.js   Livewire + Alpine store (tersimpan, catatan)
resources/js/react/   Inertia + React: Pages/, components/, lib/ (store, url), app.tsx, ssr.tsx
bench/                benchmark (run.mjs) dan pembuat tabel (report.mjs)
prototype/            prototipe JS asli + export.js (sumber catalog.json)
docs/wireframes.html  lembar sketsa
```

Data diperbarui dengan `node prototype/export.js`.

## Tes

```sh
php artisan test                                   # rute, filter, hasil kosong, form, komponen Livewire
BASE=http://127.0.0.1:8000 node tests/browser/smoke.mjs   # alur di browser, desktop + mobile
```

Smoke test browser memakai selektor yang sama untuk kedua frontend.

## Benchmark

```sh
npm run build                 # client + SSR
node bench/run.mjs            # kedua frontend, 10 run per skenario (±15 menit)
node bench/report.mjs         # tulis bench/results/RESULTS.md
```

`run.mjs` menjalankan tiap frontend bergantian dalam mode produksi (config/route/view cache,
opcache, `php -S` dengan 4 worker, React ditambah proses SSR Node), di belakang proxy kecil yang
mengompres respons dengan brotli seperti nginx/CDN. Chromium meniru HP kelas menengah:
viewport 412×823, CPU 4× lebih lambat, jaringan slow 4G gaya Lighthouse (RTT 150 ms, 1,6 Mbps).
Font Google diblokir untuk keduanya.

Skenario: muat dingin listing dan detail (TTFB, FCP, LCP, TBT, byte), kapan ♡ pertama merespons,
pindah listing → detail dan kembali, filter lewat sheet di HP, dan membuka halaman tersimpan.
Waktu render server diukur terpisah tanpa throttling.
