# EventHub Jabodetabek

Katalog vendor acara Jabodetabek yang dikurasi tim. Pengunjung menjelajah, lalu menghubungi vendor
atau EO lewat WhatsApp. Klik itu satu-satunya konversi.

Situs publik dibangun dengan **Laravel 12 + Blade + Livewire 3 + Alpine**. Pilihan ini hasil
perbandingan dengan React (Inertia + SSR) di HP kelas menengah: lihat
[docs/decisions/2026-09-frontend-bench](docs/decisions/2026-09-frontend-bench/README.md).

Tahap ini masih wireframe (gaya pensil, placeholder putus-putus, lapis catatan pena biru).
Backend (database, Filament, cache, pencatatan klik) belum dibuat; data dibaca dari
`resources/data/catalog.json`, hasil ekspor prototipe.

## Menjalankan

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
npm run build
php artisan serve          # http://127.0.0.1:8000
```

Tidak perlu database. Untuk mencoba di HP pada Wi-Fi yang sama: `php artisan serve --host=0.0.0.0`.

## Rute

`/`, `/wedding`, `/corporate`, `/{jenis}/{kategori}[/{area}]`, `/{jenis}/{kategori}/{vendor}`,
`/{jenis}/bundle[/{paket}]`, `/bundle`, `/koleksi[/{slug}]`, `/tersimpan`, `/kasih-tau-kami`.
Filter listing ada di query string, mis.
`/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor` (hasil kosong).

Pendukung: `/go/{ref}` meneruskan ke wa.me dengan kode referral, `/tersimpan/data?keys=` (JSON
untuk tabel banding).

## Struktur

```
app/Catalog/          data dan logika katalog
  Catalog.php         baca catalog.json (di-cache sebagai PHP di bootstrap/cache)
  Filters.php         definisi filter per jenis × kategori
  Pages.php           props siap-tampil per halaman
  Present.php, Copy.php  format, kartu, tulisan, catatan pena
app/Http/Controllers/SiteController.php
app/Livewire/Listing.php + resources/views/livewire/listing.blade.php
resources/views/      layout, komponen, halaman
resources/css/site.css  gaya wireframe
resources/js/blade.js   Livewire + Alpine store (tersimpan, catatan)
bench/                benchmark performa (run.mjs) dan pembuat tabel (report.mjs)
prototype/            prototipe JS asli + export.js (sumber catalog.json)
docs/                 lembar sketsa dan catatan keputusan
```

Data diperbarui dengan `node prototype/export.js`.

## Tes

```sh
php artisan test                                          # rute, filter, hasil kosong, form, Livewire
BASE=http://127.0.0.1:8000 node tests/browser/smoke.mjs   # alur di browser, desktop + HP
```

## Benchmark

```sh
npm run build
node bench/run.mjs            # 10 run per skenario
node bench/report.mjs         # tulis bench/results/RESULTS.md
```

Situs dijalankan dalam mode produksi (config/route/view cache, opcache, `php -S` dengan 4 worker)
di belakang proxy kecil yang mengompres respons dengan brotli seperti nginx/CDN. Chromium meniru HP
kelas menengah: viewport 412×823, CPU 4× lebih lambat, jaringan slow 4G gaya Lighthouse
(RTT 150 ms, 1,6 Mbps). Font Google diblokir.
