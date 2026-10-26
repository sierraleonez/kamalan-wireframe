<?php

return [
    // Penyimpanan semua foto konten (vendor, hero, jenis acara, kategori): 'public' (server ini) atau 'r2'.
    // Pindah ke R2: isi R2_* di .env, jalankan `php artisan media:copy public r2`, lalu set MEDIA_DISK=r2.
    'disk' => env('MEDIA_DISK', 'public'),

    // Lebar tiap ukuran foto vendor (px); tidak pernah diperbesar.
    'vendor_sizes' => ['card' => 640, 'gallery' => 1280, 'full' => 2048],

    'vendor_max_photos' => 30,
];
