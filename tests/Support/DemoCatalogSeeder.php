<?php

namespace Tests\Support;

use App\Catalog\CatalogDefaults;
use App\Catalog\VendorText;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Katalog demo untuk tes (tests/fixtures/demo-catalog.json, ekspor prototipe): 280 vendor
 * rekaan, bundle, dan koleksi. Tidak dipakai di situs; data asli ada di database/data/catalog.json.
 * Ditulis dengan query builder supaya cepat (dipakai di setiap tes).
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $raw = json_decode(file_get_contents(base_path('tests/fixtures/demo-catalog.json')), true, 512, JSON_THROW_ON_ERROR);
        $now = now();
        $stamp = ['created_at' => $now, 'updated_at' => $now];
        $json = fn (array $a) => json_encode($a, JSON_UNESCAPED_UNICODE);

        DB::transaction(function () use ($raw, $stamp, $json) {
            $occ = [];
            foreach (array_values($raw['types']) as $i => $t) {
                $occ[$t['slug']] = DB::table('occasions')->insertGetId([
                    'slug' => $t['slug'], 'name' => $t['name'], 'guest' => $t['guest'], 'noun' => $t['noun'],
                    'phrase' => $t['phrase'], 'pane' => $t['pane'], 'blurb' => $t['blurb'], 'sort' => $i,
                ] + $stamp);
            }

            $cat = [];
            $code = [];
            foreach (array_values($raw['categories']) as $i => $c) {
                $cat[$c['slug']] = DB::table('categories')->insertGetId([
                    'slug' => $c['slug'], 'name' => $c['name'], 'code' => $c['code'], 'unit' => $c['unit'], 'sort' => $i,
                ] + $stamp);
                $code[$c['slug']] = $c['code'];
            }

            $links = [];
            foreach ($raw['types'] as $t) {
                foreach ($t['cats'] as $i => $c) {
                    $links[] = ['occasion_id' => $occ[$t['slug']], 'category_id' => $cat[$c], 'h1' => $raw['categories'][$c]['h1'][$t['slug']], 'sort' => $i];
                }
            }
            DB::table('category_occasion')->insert($links);

            $area = [];
            foreach ($raw['areas'] as $i => $a) {
                $area[$a['slug']] = DB::table('areas')->insertGetId([
                    'slug' => $a['slug'], 'name' => $a['name'], 'short' => $a['short'],
                    'hoods' => $json($a['hoods']), 'near' => $json($a['near']), 'sort' => $i,
                ] + $stamp);
            }

            $vendor = [];
            $rows = [];
            $pivot = [];
            foreach ($raw['vendors'] as $v) {
                $vendor[$v['slug']] = $v['id'];
                $rows[] = [
                    'id' => $v['id'], 'slug' => $v['slug'], 'name' => $v['name'],
                    'category_id' => $cat[$v['cat']], 'area_id' => $area[$v['area']], 'hood' => $v['hood'],
                    'cap_min' => $v['cap'][0] ?? null, 'cap_max' => $v['cap'][1] ?? null,
                    'price' => $v['price'], 'setting' => $v['setting'], 'promoted' => $v['promoted'], 'rank' => $v['rank'],
                    'rooftop' => $v['rooftop'], 'catering_bebas' => $v['cateringBebas'], 'transit' => $v['transit'],
                    'parkir' => $v['parkir'], 'bus' => $v['bus'], 'av' => $v['av'], 'pkp' => $v['pkp'], 'halal' => $v['halal'],
                    'ref' => $v['ref'], 'wa' => $v['wa'], 'ig' => $v['ig'], 'web' => $v['web'], 'photos' => $v['photos'],
                ] + $stamp;
                foreach ($v['types'] as $t) {
                    $pivot[] = ['vendor_id' => $v['id'], 'occasion_id' => $occ[$t]];
                }
            }
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('vendors')->insert($chunk);
            }
            foreach (array_chunk($pivot, 500) as $chunk) {
                DB::table('occasion_vendor')->insert($chunk);
            }

            $members = [];
            foreach ($raw['bundles'] as $i => $b) {
                $id = DB::table('bundles')->insertGetId([
                    'slug' => $b['slug'], 'occasion_id' => $occ[$b['type']], 'title' => $b['title'], 'area_id' => $area[$b['area']],
                    'promoted' => $b['promoted'] ?? false, 'eo_vendor_id' => $vendor[$b['eo']] ?? null,
                    'guests' => $b['guests'], 'min_guests' => $b['minGuests'] ?? null,
                    'price_min' => $b['priceMin'], 'price_max' => $b['priceMax'] ?? null, 'per_head' => $b['perHead'],
                    'included' => $json($b['included']), 'excluded' => $json($b['excluded']),
                    'ref' => $b['ref'], 'wa' => $b['wa'], 'validity' => $b['validity'], 'sort' => $i,
                ] + $stamp);
                foreach ($b['members'] as $j => [$c, $slug]) {
                    $members[] = ['bundle_id' => $id, 'category_id' => $cat[$c], 'vendor_id' => $vendor[$slug], 'sort' => $j];
                }
            }
            DB::table('bundle_members')->insert($members);

            $picks = [];
            foreach ($raw['collections'] as $i => $c) {
                $id = DB::table('collections')->insertGetId([
                    'slug' => $c['slug'], 'occasion_id' => $occ[$c['type']], 'category_id' => $cat[$c['cat']],
                    'area_id' => $c['area'] ? $area[$c['area']] : null, 'title' => $c['title'], 'short' => $c['short'],
                    'locks' => $json($c['locks']), 'intro' => $c['intro'], 'sponsor' => $c['sponsor'] ?? null, 'sort' => $i,
                ] + $stamp);
                foreach ($c['picks'] as $j => $slug) {
                    $picks[] = ['collection_id' => $id, 'vendor_id' => $vendor[$slug], 'sort' => $j];
                }
            }
            DB::table('collection_picks')->insert($picks);
        });

        CatalogDefaults::avEverywhere();

        // Salin teks halaman vendor dari template ke database supaya bisa diedit di admin.
        VendorText::fillAll();
    }
}
