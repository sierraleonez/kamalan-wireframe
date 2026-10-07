<?php

namespace Tests;

use App\Catalog\Catalog;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Setiap tes memakai katalog awal dari catalog.json. */
    protected bool $seed = true;

    protected string $seeder = CatalogSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Catalog::flush();
    }
}
