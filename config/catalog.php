<?php

return [
    // Simpan katalog yang sudah dibangun di bootstrap/cache/catalog.php (ikut opcache).
    // Dimatikan saat tes supaya tiap tes membaca database-nya sendiri.
    'file_cache' => env('CATALOG_FILE_CACHE', true),
];
