<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Catalog\CatalogData;
use App\Models\Bundle;
use App\Models\Collection;
use App\Models\Vendor;
use Database\Seeders\CatalogSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RealCatalogTest extends TestCase
{
    private function emptyCatalog(): void
    {
        foreach (array_reverse(CatalogData::TABLES) as $table) {
            DB::table($table)->delete();
        }
    }

    public function test_seeder_loads_the_exported_real_catalog(): void
    {
        $data = json_decode(file_get_contents(CatalogData::path()), true);
        $this->emptyCatalog();
        $this->seed(CatalogSeeder::class);

        foreach (CatalogData::TABLES as $table) {
            $this->assertSame(count($data[$table]), DB::table($table)->count(), $table);
        }
        $this->assertNotNull(Catalog::vendor($data['vendors'][0]['slug']));
        $this->assertNull(Catalog::vendor('ballroom-kebayoran'), 'tidak ada vendor demo');
    }

    public function test_export_and_import_give_the_same_catalog(): void
    {
        Catalog::flush();
        $before = Catalog::build();
        $data = json_decode(json_encode(CatalogData::export()), true);
        $this->emptyCatalog();
        CatalogData::import($data);

        $this->assertEquals($before, Catalog::build());
    }

    public function test_empty_sections_are_hidden_when_only_venues_exist(): void
    {
        Bundle::query()->delete();
        Collection::query()->delete();
        Vendor::whereHas('category', fn ($q) => $q->where('slug', '!=', 'venue'))->delete();
        Catalog::flush();

        $this->get('/')->assertOk()
            ->assertDontSee('href="/bundle"', false)->assertDontSee('href="/koleksi"', false)
            ->assertDontSee('Nggak Perlu Hubungi Vendor Satu per Satu')->assertDontSee('Sudah Kami Kurasi')
            ->assertSee('Vendor Terbaik')
            ->assertSee('/wedding/venue', false)->assertDontSee('/wedding/catering', false);
        $this->get('/corporate')->assertOk()->assertDontSee('/corporate/catering', false)->assertDontSee('Biaya Sesuai Jumlah Peserta');
        $this->get('/bundle')->assertOk()->assertSee('Belum ada');
        $this->get('/koleksi')->assertOk()->assertSee('Koleksi sedang kami susun');
        $this->get('/wedding/venue/ballroom-kebayoran')->assertOk();
    }
}
