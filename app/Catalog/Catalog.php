<?php

namespace App\Catalog;

/**
 * Katalog baca-saja dari resources/data/catalog.json (diekspor dari prototipe).
 * Hasil indeks disimpan sebagai file PHP di bootstrap/cache agar ikut opcache.
 */
final class Catalog
{
    private static ?array $data = null;

    public static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $json = resource_path('data/catalog.json');
        $cache = base_path('bootstrap/cache/catalog.php');

        if (! is_file($cache) || filemtime($cache) < filemtime($json)) {
            $raw = json_decode(file_get_contents($json), true, 512, JSON_THROW_ON_ERROR);
            $tmp = $cache.'.'.getmypid().'.tmp';
            file_put_contents($tmp, '<?php return '.var_export(self::index($raw), true).';');
            rename($tmp, $cache);
        }

        return self::$data = require $cache;
    }

    private static function index(array $raw): array
    {
        $areas = [];
        foreach ($raw['areas'] as $i => $a) {
            $areas[$a['slug']] = $a + ['order' => $i];
        }

        $vendors = [];
        $refs = [];
        foreach ($raw['vendors'] as $v) {
            $vendors[$v['slug']] = $v;
            $refs[$v['ref']] = ['v', $v['slug']];
        }

        $bundles = [];
        foreach ($raw['bundles'] as $b) {
            $bundles[$b['slug']] = $b;
            $refs[$b['ref']] = ['b', $b['slug']];
        }

        $collections = [];
        foreach ($raw['collections'] as $c) {
            $collections[$c['slug']] = $c;
        }

        return [
            'areas' => $areas,
            'types' => $raw['types'],
            'categories' => $raw['categories'],
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
