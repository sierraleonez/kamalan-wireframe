# Hasil benchmark: Blade vs React

- Tanggal: 2026-09-26 · 10 run per skenario, nilai median (p75 dalam kurung)
- Perangkat: 412×823 @2.625x, CPU 4× lebih lambat
- Jaringan: RTT 150 ms, turun 1638 Kbps, naik 675 Kbps
- Server: php -S (4 worker) + opcache, config/route/view cache, di belakang proxy brotli q5; React + node SSR
- Catatan: font Google diblokir untuk kedua frontend

Kolom "React vs Blade": positif berarti React lebih lambat atau lebih besar.

## Server (tanpa throttling)

| | Blade | React | React vs Blade |
|---|---|---|---|
| Render listing | 15 ms | 20 ms | +33% |
| Render detail | 11 ms | 17 ms | +55% |

## Muat dingin listing (HP)

| | Blade | React | React vs Blade |
|---|---|---|---|
| TTFB | 19 ms (20 ms) | 30 ms (32 ms) | +58% |
| FCP | 574 ms (588 ms) | 580 ms (588 ms) | +1% |
| LCP | 574 ms (588 ms) | 580 ms (588 ms) | +1% |
| Total Blocking Time | 130 ms (169 ms) | 151 ms (163 ms) | +16% |
| load event | 871 ms (907 ms) | 1.215 ms (1.241 ms) | +39% |
| HTML | 7 KB (7 KB) | 7 KB (7 KB) | ≈ |
| JavaScript | 61 KB (61 KB) | 138 KB (138 KB) | +126% |
| CSS | 5 KB (5 KB) | 5 KB (5 KB) | ≈ |
| Total transfer | 73 KB (73 KB) | 149 KB (149 KB) | +104% |

## Muat dingin detail (HP)

| | Blade | React | React vs Blade |
|---|---|---|---|
| TTFB | 15 ms (15 ms) | 25 ms (27 ms) | +67% |
| FCP | 560 ms (568 ms) | 576 ms (588 ms) | +3% |
| LCP | 560 ms (568 ms) | 576 ms (588 ms) | +3% |
| Total Blocking Time | 39 ms (43 ms) | 132 ms (166 ms) | +238% |
| JavaScript | 61 KB (61 KB) | 135 KB (135 KB) | +121% |
| Total transfer | 71 KB (71 KB) | 145 KB (145 KB) | +104% |

## Interaktivitas

| | Blade | React | React vs Blade |
|---|---|---|---|
| ♡ pertama merespons (dari awal navigasi) | 899 ms (931 ms) | 1.556 ms (1.581 ms) | +73% |

## Navigasi di dalam situs

| | Blade | React | React vs Blade |
|---|---|---|---|
| Listing → detail | 443 ms (454 ms) | 632 ms (672 ms) | +43% |
| Kembali ke listing | 110 ms (129 ms) | 36 ms (38 ms) | -67% |
| Transfer saat pindah | 10 KB (10 KB) | 5 KB (5 KB) | -50% |

## Filter di HP

| | Blade | React | React vs Blade |
|---|---|---|---|
| Buka sheet filter | 458 ms (534 ms) | 225 ms (238 ms) | -51% |
| Ketuk chip → hitungan baru | 294 ms (305 ms) | 224 ms (230 ms) | -24% |
| Terapkan filter | 584 ms (599 ms) | 317 ms (327 ms) | -46% |
| Ganti urutan | 284 ms (296 ms) | 272 ms (276 ms) | -4% |

## Tersimpan

| | Blade | React | React vs Blade |
|---|---|---|---|
| Buka /tersimpan sampai tabel tampil | 951 ms (954 ms) | 944 ms (962 ms) | -1% |
