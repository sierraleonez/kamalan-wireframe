<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Katalog awal. Akun admin dibuat terpisah: php artisan make:filament-user
     */
    public function run(): void
    {
        $this->call(CatalogSeeder::class);
    }
}
