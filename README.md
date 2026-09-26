# EventHub Jabodetabek — bake-off frontend

Katalog vendor acara Jabodetabek. Repo ini sedang dipakai untuk **memilih stack frontend**:
situs publik yang sama dibangun dua kali di atas satu aplikasi Laravel, lalu dibandingkan.

| Frontend | Status | Isi |
| --- | --- | --- |
| `blade` | selesai | Blade + Livewire 3 (listing, filter, sheet) + Alpine (♡ tersimpan, popover, catatan) + `wire:navigate` |
| `react` | berikutnya | Inertia 2 + React + SSR |

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
