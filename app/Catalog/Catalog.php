<?php

namespace App\Catalog;

use Illuminate\Support\Facades\DB;

/**
 * Katalog baca-saja untuk situs publik. Dibangun dari database (diedit lewat /admin) menjadi
 * array dengan bentuk yang sama seperti ekspor prototipe dulu, lalu disimpan sebagai file PHP
 * di bootstrap/cache agar ikut opcache. Model katalog memanggil flush() setiap kali disimpan.
 */
final class Catalog
{
    /**
     * Jenis acara. Daftarnya tetap karena tulisan dan filter per jenis ada di kode (Copy, Filters);
     * nama dan teksnya diedit di admin. Dipakai juga untuk pola rute tanpa menyentuh database.
     */
    public const TYPES = ['wedding', 'corporate', 'ulang-tahun', 'baby-kids', 'social-gathering', 'perayaan'];

    private static ?array $data = null;

    private static bool $flushQueued = false;

    public static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $cache = self::cacheFile();
        if ($cache === null) {
            return self::$data = self::build();
        }

        if (! is_file($cache)) {
            $tmp = $cache.'.'.getmypid().'.tmp';
            file_put_contents($tmp, '<?php return '.var_export(self::build(), true).';');
            rename($tmp, $cache);
            self::invalidate($cache);
        }

        return self::$data = require $cache;
    }

    /** Buang cache; permintaan berikutnya membangun ulang dari database. */
    public static function flush(): void
    {
        self::dropCache();

        // Admin menyimpan relasi (jenis acara, anggota bundle) setelah model induknya; buang sekali
        // lagi di akhir permintaan supaya cache tidak dibangun dari data setengah tersimpan.
        if (! self::$flushQueued && app()->isBooted()) {
            self::$flushQueued = true;
            app()->terminating(function () {
                self::$flushQueued = false;
                self::dropCache();
            });
        }
    }

    private static function dropCache(): void
    {
        self::$data = null;
        $cache = self::cacheFile();
        if ($cache !== null && is_file($cache)) {
            @unlink($cache);
            self::invalidate($cache);
        }
    }

    private static function cacheFile(): ?string
    {
        return config('catalog.file_cache', true) ? base_path('bootstrap/cache/catalog.php') : null;
    }

    /** Dengan opcache.validate_timestamps=0 file baru tidak terbaca tanpa ini. */
    private static function invalidate(string $file): void
    {
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
    }

    /** Susun katalog dari database. Sepuluh query, tanpa N+1. */
    public static function build(): array
    {
        $occasions = DB::table('occasions')->orderBy('sort')->get()->keyBy('id');
        $categories = DB::table('categories')->orderBy('sort')->get()->keyBy('id');
        $areaRows = DB::table('areas')->orderBy('sort')->orderBy('id')->get()->keyBy('id');
        $links = DB::table('category_occasion')->orderBy('sort')->get();

        $types = [];
        foreach ($occasions as $o) {
            $types[$o->slug] = [
                'slug' => $o->slug, 'name' => $o->name, 'guest' => $o->guest, 'cats' => [],
                'noun' => $o->noun, 'phrase' => $o->phrase, 'pane' => $o->pane, 'blurb' => $o->blurb,
            ];
        }

        $cats = [];
        foreach ($categories as $c) {
            $cats[$c->slug] = ['slug' => $c->slug, 'name' => $c->name, 'code' => $c->code, 'unit' => $c->unit, 'h1' => []];
        }
        foreach ($links as $l) {
            $types[$occasions[$l->occasion_id]->slug]['cats'][] = $categories[$l->category_id]->slug;
        }
        // h1 berurutan mengikuti jenis acara, seperti di ekspor lama.
        $byOcc = $links->groupBy('occasion_id');
        foreach ($occasions as $o) {
            foreach ($byOcc[$o->id] ?? [] as $l) {
                $cats[$categories[$l->category_id]->slug]['h1'][$o->slug] = $l->h1;
            }
        }

        $areas = [];
        $i = 0;
        foreach ($areaRows as $a) {
            $areas[$a->slug] = [
                'slug' => $a->slug, 'name' => $a->name, 'short' => $a->short,
                'hoods' => json_decode($a->hoods, true), 'near' => json_decode($a->near, true), 'order' => $i++,
            ];
        }
        foreach ($areas as &$a) {
            $a['near'] = array_values(array_filter($a['near'], fn ($s) => isset($areas[$s])));
        }
        unset($a);

        $occOf = [];
        foreach (DB::table('occasion_vendor')->get() as $p) {
            $occOf[$p->vendor_id][] = $occasions[$p->occasion_id];
        }

        $vendors = [];
        $slugOf = [];
        $refs = [];
        foreach (DB::table('vendors')->orderBy('id')->get() as $v) {
            $vt = $occOf[$v->id] ?? [];
            usort($vt, fn ($a, $b) => $a->sort <=> $b->sort);
            $slugOf[$v->id] = $v->slug;
            $vendors[$v->slug] = [
                'name' => $v->name,
                'cat' => $categories[$v->category_id]->slug,
                'area' => $areaRows[$v->area_id]->slug,
                'hood' => $v->hood,
                'cap' => $v->cap_min === null ? null : [(int) $v->cap_min, (int) $v->cap_max],
                'price' => (int) $v->price,
                'setting' => $v->setting,
                'types' => array_map(fn ($o) => $o->slug, $vt),
                'promoted' => (bool) $v->promoted,
                'rank' => (int) $v->rank,
                'rooftop' => (bool) $v->rooftop,
                'cateringBebas' => (bool) $v->catering_bebas,
                'transit' => (bool) $v->transit,
                'parkir' => (int) $v->parkir,
                'bus' => (int) $v->bus,
                'av' => (bool) $v->av,
                'pkp' => (bool) $v->pkp,
                'halal' => (bool) $v->halal,
                'id' => (int) $v->id,
                'slug' => $v->slug,
                'ref' => $v->ref,
                'wa' => $v->wa,
                'ig' => $v->ig,
                'web' => $v->web,
                'photos' => (int) $v->photos,
                'writeup' => $v->writeup,
                'specs' => $v->specs === null ? [] : json_decode($v->specs, true),
                // Teks yang diedit di admin; null = pakai template (Copy, Present).
                'priceNote' => $v->price_note,
                'included' => $v->included === null ? null : array_map(fn ($i) => [$i['label'], $i['note'] ?? ''], json_decode($v->included, true)),
                'faqs' => $v->faqs === null ? null : json_decode($v->faqs, true),
                'occasionNotes' => $v->occasion_notes === null ? null : array_column(array_map(
                    fn ($n) => ['occasion' => $n['occasion'], 'title' => $n['title'], 'paras' => VendorText::paras($n['text'] ?? '')],
                    json_decode($v->occasion_notes, true),
                ), null, 'occasion'),
            ];
            $refs[$v->ref] = ['v', $v->slug];
        }

        $members = DB::table('bundle_members')->orderBy('sort')->get()->groupBy('bundle_id');
        $bundles = [];
        foreach (DB::table('bundles')->orderBy('sort')->orderBy('id')->get() as $b) {
            $bundles[$b->slug] = [
                'slug' => $b->slug,
                'type' => $occasions[$b->occasion_id]->slug,
                'title' => $b->title,
                'area' => $areaRows[$b->area_id]->slug,
                'promoted' => (bool) $b->promoted,
                'eo' => $b->eo_vendor_id ? ($slugOf[$b->eo_vendor_id] ?? null) : null,
                'guests' => (int) $b->guests,
                'minGuests' => $b->min_guests === null ? null : (int) $b->min_guests,
                'priceMin' => (int) $b->price_min,
                'priceMax' => $b->price_max === null ? null : (int) $b->price_max,
                'perHead' => (bool) $b->per_head,
                'members' => array_values(array_filter(array_map(
                    fn ($m) => isset($slugOf[$m->vendor_id]) ? [$categories[$m->category_id]->slug, $slugOf[$m->vendor_id]] : null,
                    ($members[$b->id] ?? collect())->all(),
                ))),
                'included' => json_decode($b->included, true),
                'excluded' => json_decode($b->excluded, true),
                'ref' => $b->ref,
                'wa' => $b->wa,
                'validity' => $b->validity,
            ];
            $refs[$b->ref] = ['b', $b->slug];
        }

        $picks = DB::table('collection_picks')->orderBy('sort')->get()->groupBy('collection_id');
        $collections = [];
        foreach (DB::table('collections')->orderBy('sort')->orderBy('id')->get() as $c) {
            $collections[$c->slug] = [
                'slug' => $c->slug,
                'type' => $occasions[$c->occasion_id]->slug,
                'cat' => $categories[$c->category_id]->slug,
                'area' => $c->area_id ? $areaRows[$c->area_id]->slug : null,
                'title' => $c->title,
                'short' => $c->short,
                'locks' => json_decode($c->locks, true),
                'intro' => $c->intro,
                'picks' => array_values(array_filter(array_map(fn ($p) => $slugOf[$p->vendor_id] ?? null, ($picks[$c->id] ?? collect())->all()))),
                'sponsor' => $c->sponsor,
            ];
        }

        return [
            'areas' => $areas,
            'types' => $types,
            'categories' => $cats,
            'vendors' => $vendors,
            'bundles' => $bundles,
            'collections' => $collections,
            'refs' => $refs,
        ];
    }

    public static function areas(): array
    {
        return self::data()['areas'];
    }

    public static function area(?string $slug): ?array
    {
        return $slug === null ? null : (self::data()['areas'][$slug] ?? null);
    }

    public static function types(): array
    {
        return self::data()['types'];
    }

    public static function type(string $slug): ?array
    {
        return self::data()['types'][$slug] ?? null;
    }

    public static function category(string $slug): ?array
    {
        return self::data()['categories'][$slug] ?? null;
    }

    public static function vendor(string $slug): ?array
    {
        return self::data()['vendors'][$slug] ?? null;
    }

    public static function bundles(): array
    {
        return self::data()['bundles'];
    }

    public static function bundle(string $slug): ?array
    {
        return self::data()['bundles'][$slug] ?? null;
    }

    public static function collections(): array
    {
        return self::data()['collections'];
    }

    public static function collection(string $slug): ?array
    {
        return self::data()['collections'][$slug] ?? null;
    }

    /** @return array{0:string,1:array}|null ['v'|'b', item] */
    public static function byRef(string $ref): ?array
    {
        $hit = self::data()['refs'][$ref] ?? null;
        if (! $hit) {
            return null;
        }

        return [$hit[0], $hit[0] === 'v' ? self::vendor($hit[1]) : self::bundle($hit[1])];
    }

    public static function hasCategory(string $type, string $cat): bool
    {
        $t = self::type($type);

        return $t !== null && in_array($cat, $t['cats'], true);
    }

    /** Vendor untuk jenis acara × kategori × (area). */
    public static function vendorsOf(string $type, string $cat, ?string $area = null): array
    {
        $out = [];
        foreach (self::data()['vendors'] as $v) {
            if ($v['cat'] === $cat && in_array($type, $v['types'], true) && ($area === null || $v['area'] === $area)) {
                $out[] = $v;
            }
        }

        return $out;
    }

    public static function editorialSort(array &$list): void
    {
        $areas = self::areas();
        usort($list, fn ($a, $b) => [$a['rank'], $areas[$a['area']]['order'], $a['id']] <=> [$b['rank'], $areas[$b['area']]['order'], $b['id']]);
    }

    public static function firstEditorial(string $type, string $cat, ?string $area = null): ?array
    {
        $list = self::vendorsOf($type, $cat, $area);
        self::editorialSort($list);

        return $list[0] ?? null;
    }
}
