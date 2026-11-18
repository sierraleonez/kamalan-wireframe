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

Data katalog ada di MySQL dan diedit tim lewat panel admin Filament di `/admin`: vendor, bundle,
koleksi, area, dan jenis acara (bisa ditambah; jenis acara baru memakai teks umum di halaman
vendor). Daftar kategori tetap, karena filter dan tulisan per kategori ada di kode; namanya bisa
diedit, tapi tidak bisa ditambah atau dihapus. Kiriman form "Kasih tau kami" tersimpan sebagai permintaan
dan bisa ditandai sudah ditangani. Pencatatan klik WhatsApp belum ada. Vendor tanpa foto tampil dengan placeholder gradien.

## Menjalankan

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate   # isi DB_PASSWORD dan DB_ROOT_PASSWORD
docker compose up -d       # MySQL 8.4 di 127.0.0.1:3306
php artisan migrate --seed # tabel + katalog asli dari database/data/catalog.json
php artisan make:filament-user                     # akun admin
npm run build
php artisan serve          # http://127.0.0.1:8000, admin di /admin
```

Untuk mencoba di HP pada Wi-Fi yang sama: `php artisan serve --host=0.0.0.0`.

`database/data/catalog.json` hanya dipakai seeder untuk mengisi database pertama kali; setelah itu
database yang jadi sumber data. Isinya salinan katalog asli (jenis acara, kategori, area, vendor,
foto, bundle, koleksi, konten beranda), dibuat ulang dengan `php artisan catalog:export` kalau tim
sudah menambah data. Akun admin, permintaan, dan pendaftaran vendor tidak ikut. Foto dirujuk lewat
path di penyimpanan media: tampil kalau MEDIA_DISK menunjuk ke R2 yang sama, atau setelah
`php artisan media:copy r2 public`. `php artisan migrate:fresh --seed` mengembalikan katalog ke isi
ekspor terakhir (dan menghapus editan sesudahnya serta akun admin).

Bagian situs yang kosong (kategori tanpa vendor, bundle, koleksi) tidak ditampilkan sampai ada
isinya. `php artisan media:prune` menampilkan foto di `content/` yang tidak dipakai lagi;
tambahkan `--delete` untuk menghapusnya.

### Foto beranda

Foto hero diatur di Admin → **Konten beranda**; foto sampul kartu jenis acara (wajib) di
**Jenis acara**; foto kotak kategori di **Kategori**. Foto bisa
diunggah (klik atau seret) atau diambil dari link; server memperkecilnya dan menyimpannya sebagai
WebP di `storage/app/public/content` (perlu `php artisan storage:link`). Tanpa foto, tampil gradien
dan ikon seperti sebelumnya.

### Foto vendor

Diatur di Admin → **Vendor** → ubah → bagian **Foto** (unggah beberapa sekaligus atau tempel link;
urutan, keterangan, sampul). Tiap foto disimpan dalam tiga ukuran WebP (card 640, gallery 1280,
full 2048 px). Di situs: halaman vendor memakai galeri (sampul + empat kecil, "+N foto", penampil
layar penuh dengan panah/geser); kartu vendor, isi paket, koleksi, dan Tersimpan memakai sampul;
paket memakai foto vendor anggota pertama yang punya foto; pratinjau link (WhatsApp) memakai sampul.

### Akun pengunjung

Pengunjung masuk tanpa kata sandi: **Google**, atau **email + kode 6 digit** (berlaku 10 menit, sekali
pakai, disimpan sebagai hash; maks. 5 percobaan per kode dan 5 kode per email per jam). Daftar dan masuk
satu alur; akun baru lalu mengisi nama di halaman terpisah. Dengan akun aktif, ♡ dan catatan Tersimpan
disimpan di akun (tabel `saves`); tamu yang menekan ♡ diajak masuk lalu kembali dengan vendornya
tersimpan. Simpanan lama di browser pindah ke akun saat pertama masuk.

Diatur di `.env`:

```sh
ACCOUNTS=true                       # tanpa ini situs berjalan seperti dulu (♡ di browser saja)
GOOGLE_CLIENT_ID=… GOOGLE_CLIENT_SECRET=…   # tombol Google tampil kalau keduanya diisi
LOGIN_EMAIL=true                    # setelah MAIL_* menunjuk ke layanan pengirim email
```

Redirect URI Google: `https://kamalan.eventaries.cloud/auth/google/callback`. Hanya akun `is_admin`
yang bisa membuka /admin; admin baru: `php artisan make:filament-user` lalu
`php artisan user:admin email@…`. Daftar pengunjung ada di Admin → Pengunjung → Akun pengunjung
(hapus akun = permintaan hapus data). Kebijakan privasi: /privasi.

### Badge Verified

Admin → Vendor → ubah → **Verifikasi**: empat cek (dikunjungi tim, harga dari pengelola, NIB, WhatsApp
resmi) + tanggal cek. Kalau keempatnya dicentang, centang biru tampil setelah nama di kartu dan judul
halaman vendor ("Terverifikasi Kamalan · dicek …"), selama 12 bulan sejak tanggal cek; lewat dari itu
badge hilang sendiri. Ketuk centang → penjelasan. Listing punya filter "Hanya terverifikasi"; daftar
vendor di admin punya filter Verifikasi (termasuk "Perlu dicek ulang", habis ≤ 30 hari). NIB dan
catatan tidak tampil di situs.

### Ulasan pengunjung

Pengunjung yang masuk menulis satu ulasan per vendor (1–5 bintang, jenis dan bulan acara, cerita
min. 30 karakter tanpa nomor HP/link) di /ulasan/{vendor}. Setiap ulasan baru atau yang diubah
menunggu di Admin → Pengunjung → **Ulasan**: Tampilkan, atau Tolak dengan alasan (dikirim ke email
penulis; ulasan yang tampil bisa disembunyikan lagi). Hanya ulasan yang tampil masuk rata-rata.
Kartu vendor menampilkan "★ 4,8 (12)" mulai dari 3 ulasan; halaman vendor punya bagian "Ulasan
pengunjung" (rata-rata, sebaran bintang, saring per jenis acara, urutkan). Nama tampil sebagai
"Rina P.", email tidak pernah tampil.

### Server (kamalan.eventaries.cloud)

Caddy (HTTPS, file statis dari `public/`) → PHP-FPM pool `kamalan` (`/etc/php/8.4/fpm/pool.d/kamalan.conf`,
berjalan sebagai `ubuntu`, unggahan maks. 16 MB) → Laravel. `.env` produksi: `APP_ENV=production`,
`APP_DEBUG=false`. Memasang perubahan:

```sh
deploy/deploy.sh          # atau deploy/deploy.sh --pull untuk git pull --ff-only dulu
```

Skrip itu menjalankan composer, migrasi, build aset, cache rute/view/event/Filament, lalu reload
PHP-FPM. Config sengaja tidak di-cache supaya `php artisan test` di server tetap memakai SQLite dan
penyimpanan tes. Layanan lama `kamalan.service` (`artisan serve`) dimatikan; untuk kembali sementara:
`sudo systemctl enable --now kamalan.service` dan kembalikan blok `reverse_proxy 127.0.0.1:8125` di Caddyfile.

### Cache katalog

Situs tidak membaca database di setiap permintaan. `App\Catalog\Catalog` membangun seluruh katalog
sekali, menyimpannya di `bootstrap/cache/catalog.php` (ikut opcache), dan setiap kali model katalog
disimpan dari admin file itu dihapus sehingga permintaan berikutnya membangunnya ulang. Kalau data
diubah langsung lewat SQL, hapus file itu (`rm bootstrap/cache/catalog.php`). Rute tidak bergantung
pada database, jadi `php artisan route:cache` aman dijalankan saat build.

## Rute

`/`, `/{jenis}`, `/{jenis}/{kategori}[/{area}]`, `/{jenis}/{kategori}/{vendor}`,
`/{jenis}/bundle[/{paket}]`, `/bundle`, `/koleksi[/{slug}]`, `/tersimpan`, `/kasih-tau-kami`.
Filter listing ada di query string, mis.
`/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor` (hasil kosong).

`{jenis}`: slug jenis acara di database (awalnya `wedding`, `corporate`, `ulang-tahun`, `baby-kids`,
`social-gathering`, `perayaan`). Kata di `Catalog::RESERVED` (admin, bundle, koleksi, …) tidak bisa
jadi slug.

Pendukung: `/go/{ref}?t={jenis}&p={penempatan}` meneruskan ke wa.me dengan kode referral plus
penempatan tombol (`VNU-0104-DETAIL`; penempatan: HOME, LIST, DETAIL, KOLEKSI, SAVED, BUNDLE),
`/tersimpan/data?keys=` (JSON untuk tabel banding).

## Struktur

```
app/Catalog/          data dan logika katalog
  Catalog.php         bangun katalog dari database (di-cache sebagai PHP di bootstrap/cache)
  Filters.php         definisi filter per jenis × kategori
  Pages.php           props siap-tampil per halaman
  Present.php         format harga, kartu, tautan WhatsApp
  Copy.php            tulisan vendor (netral + per jenis acara), fakta, yang termasuk, FAQ
app/Models/           model katalog + Lead; setiap simpan membuang cache katalog
app/Filament/         panel admin (/admin): resource vendor, bundle, koleksi, area, jenis acara,
                      kategori, permintaan
app/Http/Controllers/SiteController.php
database/             migrasi; CatalogSeeder memuat database/data/catalog.json (katalog asli)
app/Livewire/Listing.php + resources/views/livewire/listing.blade.php
resources/views/      layout, komponen, halaman
resources/css/site.css  token design system + komponen
resources/js/blade.js   Livewire + Alpine (tersimpan, bagikan, tab jenis acara)
bench/                benchmark performa (run.mjs) dan pembuat tabel (report.mjs)
prototype/            prototipe JS asli + export.js (sumber katalog demo untuk tes)
docs/design/          design system dan halaman yang disetujui
docs/                 lembar sketsa wireframe dan catatan keputusan
```


## Tes

```sh
php artisan test                                          # rute, filter, form, Livewire, database, admin
BASE=http://127.0.0.1:8000 node tests/browser/smoke.mjs   # alur di browser, desktop + HP
```

`php artisan test` memakai SQLite di memori yang diisi katalog demo (`tests/fixtures/demo-catalog.json`,
lewat `Tests\Support\DemoCatalogSeeder`) di setiap tes, jadi tidak perlu MySQL dan tes tidak
bergantung pada data yang diedit tim.

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
