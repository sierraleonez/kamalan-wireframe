# Hasil benchmark

- Tanggal: 2026-09-27 · 5 run per skenario, nilai median dan p75
- Perangkat: 412×823 @2.625x, CPU 4× lebih lambat
- Jaringan: RTT 150 ms, turun 1638 Kbps, naik 675 Kbps
- Server: php -S (4 worker) + opcache, config/route/view cache, di belakang proxy brotli q5
- Catatan: font Google diblokir

Perbandingan dengan React: [docs/decisions/2026-09-frontend-bench](../../docs/decisions/2026-09-frontend-bench/README.md).

## Server (tanpa throttling)

| | median |
|---|---|
| Render listing | 13 ms |
| Render detail | 9 ms |

## Muat dingin listing (HP)

| | median | p75 |
|---|---|---|
| TTFB | 18 ms | 19 ms |
| FCP | 544 ms | 552 ms |
| LCP | 544 ms | 552 ms |
| Total Blocking Time | 132 ms | 156 ms |
| load event | 889 ms | 910 ms |
| HTML | 7 KB | 7 KB |
| JavaScript | 62 KB | 62 KB |
| CSS | 6 KB | 6 KB |
| Total transfer | 75 KB | 75 KB |

## Muat dingin detail (HP)

| | median | p75 |
|---|---|---|
| TTFB | 17 ms | 19 ms |
| FCP | 576 ms | 576 ms |
| LCP | 576 ms | 576 ms |
| Total Blocking Time | 69 ms | 71 ms |
| JavaScript | 62 KB | 62 KB |
| Total transfer | 75 KB | 75 KB |

## Interaktivitas

| | median | p75 |
|---|---|---|
| ♡ pertama merespons (dari awal navigasi) | 906 ms | 914 ms |

## Navigasi di dalam situs

| | median | p75 |
|---|---|---|
| Listing → detail | 464 ms | 500 ms |
| Kembali ke listing | 143 ms | 156 ms |
| Transfer saat pindah | 13 KB | 13 KB |

## Filter di HP

| | median | p75 |
|---|---|---|
| Buka sheet filter | 464 ms | 469 ms |
| Ketuk chip → hitungan baru | 319 ms | 325 ms |
| Terapkan filter | 562 ms | 570 ms |
| Ganti urutan | 275 ms | 277 ms |

## Tersimpan

| | median | p75 |
|---|---|---|
| Buka /tersimpan sampai tabel tampil | 936 ms | 939 ms |
