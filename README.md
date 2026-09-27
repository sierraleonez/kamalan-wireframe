# Kamalan Event Hub

Katalog vendor acara Jabodetabek yang dikurasi tim. Pengunjung menjelajah, lalu menghubungi vendor
atau EO lewat WhatsApp. Klik itu satu-satunya konversi.

Situs publik dibangun dengan **Laravel 12 + Blade + Livewire 3 + Alpine**. Pilihan ini hasil
perbandingan dengan React (Inertia + SSR) di HP kelas menengah: lihat
[docs/decisions/2026-09-frontend-bench](docs/decisions/2026-09-frontend-bench/README.md).

Tahap ini **high fidelity**, mengikuti design system Kamalan di [`docs/design/`](docs/design/):

- `design-system.md` — token, tipografi, komponen, aturan konten.
- `kamalan-home.html`, `kamalan-vendor-detail.html` — halaman yang disetujui. Ini sumber kebenaran:
  kalau dokumen dan halaman berbeda, halaman yang menang. Blok token `:root` di
  `resources/css/site.css` disalin apa adanya dari `kamalan-home.html`.

| Halaman | Status |
| --- | --- |
| Beranda, detail vendor | Mengikuti halaman yang disetujui |
| Listing, hasil kosong, beranda jenis acara, bundle, koleksi, tersimpan, form, 404 | Diturunkan dari design system — usulan untuk ditinjau desainer, mulai dari Listing |

Enam jenis acara: Wedding, Corporate, Ulang Tahun, Baby & Kids, Social Gathering, Perayaan. Setiap
vendor punya satu URL per jenis acara yang dilayaninya; di halaman detail, tab jenis acara mengganti
URL, breadcrumb, dan pesan WhatsApp tanpa memuat ulang halaman.

Backend (database, Filament, cache, pencatatan klik) belum dibuat; data dibaca dari
`resources/data/catalog.json`, hasil ekspor `prototype/export.js`. Foto masih placeholder gradien.

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

`/`, `/{jenis}`, `/{jenis}/{kategori}[/{area}]`, `/{jenis}/{kategori}/{vendor}`,
`/{jenis}/bundle[/{paket}]`, `/bundle`, `/koleksi[/{slug}]`, `/tersimpan`, `/kasih-tau-kami`.
Filter listing ada di query string, mis.
`/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor` (hasil kosong).

`{jenis}`: `wedding`, `corporate`, `ulang-tahun`, `baby-kids`, `social-gathering`, `perayaan`.

Pendukung: `/go/{ref}?t={jenis}&p={penempatan}` meneruskan ke wa.me dengan kode referral plus
penempatan tombol (`VNU-0104-DETAIL`; penempatan: HOME, LIST, DETAIL, KOLEKSI, SAVED, BUNDLE),
`/tersimpan/data?keys=` (JSON untuk tabel banding).

## Struktur

```
app/Catalog/          data dan logika katalog
  Catalog.php         baca catalog.json (di-cache sebagai PHP di bootstrap/cache)
  Filters.php         definisi filter per jenis × kategori
  Pages.php           props siap-tampil per halaman
  Present.php         format harga, kartu, tautan WhatsApp
  Copy.php            tulisan vendor (netral + per jenis acara), fakta, yang termasuk, FAQ
app/Http/Controllers/SiteController.php
app/Livewire/Listing.php + resources/views/livewire/listing.blade.php
resources/views/      layout, komponen, halaman
resources/css/site.css  token design system + komponen
resources/js/blade.js   Livewire + Alpine (tersimpan, bagikan, tab jenis acara)
bench/                benchmark performa (run.mjs) dan pembuat tabel (report.mjs)
prototype/            prototipe JS asli + export.js (sumber catalog.json)
docs/design/          design system dan halaman yang disetujui
docs/                 lembar sketsa wireframe dan catatan keputusan
```

Data diperbarui dengan `node prototype/export.js`.

## Tes

```sh
php artisan test                                          # rute, filter, hasil kosong, form, Livewire
BASE=http://127.0.0.1:8000 node tests/browser/smoke.mjs   # alur di browser, desktop + HP
```

Membandingkan dengan halaman yang disetujui (situs di port 8123):

```sh
node tests/browser/compare-design.mjs /tmp/banding 1280 light   # juga: 390, dark
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
