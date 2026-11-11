<?php

namespace Tests;

use App\Catalog\Catalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\DemoCatalogSeeder;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /** Setiap tes memakai katalog demo (tests/fixtures/demo-catalog.json). */
    protected bool $seed = true;

    protected string $seeder = DemoCatalogSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        Catalog::flush();
    }
}
