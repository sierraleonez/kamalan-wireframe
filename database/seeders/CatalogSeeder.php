<?php

namespace Database\Seeders;

use App\Catalog\CatalogData;
use Illuminate\Database\Seeder;

/**
 * Katalog asli dari database/data/catalog.json (dibuat dengan `php artisan catalog:export`).
 * Foto dirujuk lewat path di penyimpanan media (MEDIA_DISK); filenya tidak ikut.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        CatalogData::import(json_decode(file_get_contents(CatalogData::path()), true, 512, JSON_THROW_ON_ERROR));
    }
}
