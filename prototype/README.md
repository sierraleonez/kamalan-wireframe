# EventHub Jabodetabek — situs wireframe

Katalog vendor acara Jabodetabek yang dikurasi tim, dibangun sebagai wireframe yang bisa diklik:
gaya pensil di kertas, placeholder putus-putus, dan satu lapis catatan pena biru (tombol **✎ Catatan**).

Tanpa akun, tanpa dashboard vendor, tanpa pembayaran. Satu-satunya konversi adalah klik ke WhatsApp.

## Menjalankan

```sh
npm start            # atau: node server.js
# buka http://localhost:3000
```

Butuh Node 18+. Tidak ada dependensi dan tidak ada langkah build. `PORT=8080 npm start` untuk port lain.

## Rute

| Rute | Halaman |
| --- | --- |
| `/` | Beranda |
| `/wedding`, `/corporate` | Beranda cabang |
| `/:jenis/:kategori` | Listing seluruh Jabodetabek, mis. `/wedding/venue` |
| `/:jenis/:kategori/:area` | Listing per area, mis. `/wedding/venue/jakarta-selatan`, `/corporate/eo/tangerang` |
| `/:jenis/:kategori/:vendor` | Detail vendor, mis. `/wedding/venue/ballroom-kebayoran` |
| `/:jenis/bundle` | Daftar bundle satu cabang |
| `/:jenis/bundle/:paket` | Detail bundle, mis. `/wedding/bundle/paket-intimate-wedding-150-pax` |
| `/bundle` | Semua bundle |
| `/koleksi`, `/koleksi/:slug` | Koleksi editorial, mis. `/koleksi/rooftop-jaksel-dibawah-50jt` |
| `/tersimpan` | Perbandingan item yang disimpan (localStorage) |
| `/kasih-tau-kami` | Form "ngga nemu" |

Filter listing ada di query string, jadi setiap keadaan bisa dibagikan, termasuk hasil kosong:
`/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor`.

Kategori wedding: venue, catering, eo, hiburan, dekorasi, dokumentasi.
Kategori corporate: venue, catering, eo, av-produksi, hiburan.
Area: jakarta-selatan, jakarta-pusat, jakarta-barat, jakarta-timur, tangerang, tangerang-selatan, bekasi, depok, bogor.

## Server

`server.js` adalah server statis kecil:

- `/assets/*` dan `/docs/*` dilayani sebagai file.
- `/go?ref=…&to=…` mencatat klik WhatsApp ke `data/clicks.jsonl` lalu redirect. Tujuan hanya boleh `https://wa.me/…`.
- `POST /api/kebutuhan` menyimpan isian form ke `data/kebutuhan.jsonl`.
- Rute lain mengembalikan `index.html`; routing terjadi di browser.

Di hosting statis (Netlify, Vercel, dan sejenisnya), atur semua rute agar kembali ke `index.html`.
Pencatatan klik dan form hanya jalan dengan `server.js`; tanpanya, klik langsung ke wa.me
dan form hanya tersimpan di browser.

Dibuka dari `file://` atau di dalam iframe, situs memakai routing di memori dan menampilkan bilah alamat kecil di atas.

## Struktur

```
index.html          kerangka halaman
assets/data.js      data contoh: area, kategori, vendor, bundle, koleksi, definisi filter
assets/app.js       router, tampilan, interaksi
assets/styles.css   gaya wireframe
server.js           server statis + pencatat klik
docs/wireframes.html  lembar sketsa asli, satu halaman per rute
```

Data vendor adalah contoh. Venue wedding Jakarta Selatan ditulis tangan; sisanya dibangkitkan
deterministik dari `data.js`, jadi slug dan jumlahnya stabil di setiap muat ulang.
Nomor WhatsApp vendor adalah nomor palsu.
