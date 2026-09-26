<?php

namespace App\Catalog;

use Closure;

/**
 * Definisi filter per jenis acara × kategori (port dari EH.filtersFor di prototipe).
 * Wedding memilih suasana; corporate mencoret dengan batasan keras.
 */
final class Filters
{
    /** Semua kunci query yang mungkin dipakai filter. */
    public const KEYS = ['kapasitas', 'harga', 'tipe', 'catering-bebas', 'ruang-transit', 'parkir-100', 'halal', 'av', 'bus', 'pkp'];

    public const CAP_BANDS = [
        ['id' => 'lt150', 'label' => 'di bawah 150', 'min' => 0, 'max' => 149],
        ['id' => '150-300', 'label' => '150–300', 'min' => 150, 'max' => 300],
        ['id' => '300-500', 'label' => '300–500', 'min' => 300, 'max' => 500],
        ['id' => '500-plus', 'label' => '500+', 'min' => 500, 'max' => 100000],
    ];

    private const PRICE_BANDS = [
        'total' => [
            ['id' => 'lt30', 'label' => 'di bawah 30 jt', 'min' => 0, 'max' => 29.99],
            ['id' => '30-50', 'label' => '30–50 jt', 'min' => 30, 'max' => 50],
            ['id' => '50-80', 'label' => '50–80 jt', 'min' => 50, 'max' => 80],
            ['id' => '80-plus', 'label' => '80 jt ke atas', 'min' => 80, 'max' => 100000],
        ],
        'pax' => [
            ['id' => 'lt75', 'label' => 'di bawah 75 rb/pax', 'min' => 0, 'max' => 74.99],
            ['id' => '75-120', 'label' => '75–120 rb/pax', 'min' => 75, 'max' => 120],
            ['id' => '120-plus', 'label' => '120 rb/pax ke atas', 'min' => 120, 'max' => 100000],
        ],
        'small' => [
            ['id' => 'lt15', 'label' => 'di bawah 15 jt', 'min' => 0, 'max' => 14.99],
            ['id' => '15-30', 'label' => '15–30 jt', 'min' => 15, 'max' => 30],
            ['id' => '30-plus', 'label' => '30 jt ke atas', 'min' => 30, 'max' => 100000],
        ],
    ];

    public const SORTS = [
        'editor' => 'Pilihan editor',
        'harga' => 'Harga terendah',
        'kapasitas' => 'Kapasitas terbesar',
    ];

    public static function priceBands(string $cat): array
    {
        if (Catalog::category($cat)['unit'] === 'pax') {
            return self::PRICE_BANDS['pax'];
        }

        return in_array($cat, ['venue', 'eo', 'dekorasi'], true) ? self::PRICE_BANDS['total'] : self::PRICE_BANDS['small'];
    }

    private static function band(string $key, string $label, array $options, Closure $test, array $extra = []): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'band', 'options' => $options, 'test' => $test] + $extra;
    }

    private static function flag(string $key, string $label, string $group, Closure $test): array
    {
        return ['key' => $key, 'label' => $label, 'kind' => 'flag', 'group' => $group, 'test' => $test];
    }

    private static function find(array $options, ?string $id): ?array
    {
        foreach ($options as $o) {
            if ($o['id'] === $id) {
                return $o;
            }
        }

        return null;
    }

    public static function defs(string $type, string $cat): array
    {
        $f = [];

        if (in_array($cat, ['venue', 'catering', 'eo'], true)) {
            $f[] = self::band('kapasitas', $type === 'corporate' ? 'Jumlah peserta' : 'Kapasitas', self::CAP_BANDS,
                function (array $v, string $id) {
                    $b = self::find(self::CAP_BANDS, $id);

                    return ! $b || ($v['cap'][0] <= $b['max'] && $v['cap'][1] >= $b['min']);
                });
        }

        $pb = self::priceBands($cat);
        $f[] = self::band('harga', 'Kisaran harga', $pb, function (array $v, string $id) use ($pb) {
            $b = self::find($pb, $id);

            return ! $b || ($v['price'] >= $b['min'] && $v['price'] <= $b['max']);
        }, ['ordered' => true]);

        if ($type === 'wedding' && $cat === 'venue') {
            $f[] = self::band('tipe', 'Indoor / outdoor', [['id' => 'indoor', 'label' => 'Indoor'], ['id' => 'outdoor', 'label' => 'Outdoor']],
                fn (array $v, string $id) => $v['setting'] === 'both' || $v['setting'] === $id, ['plain' => true]);
            $f[] = self::flag('catering-bebas', 'Catering bebas', 'Fasilitas', fn ($v) => (bool) $v['cateringBebas']);
            $f[] = self::flag('ruang-transit', 'Ruang transit', 'Fasilitas', fn ($v) => (bool) $v['transit']);
            $f[] = self::flag('parkir-100', 'Parkir 100+ mobil', 'Fasilitas', fn ($v) => $v['parkir'] >= 100);
        }

        if ($type === 'wedding' && $cat === 'catering') {
            $f[] = self::flag('halal', 'Halal bersertifikat', 'Fasilitas', fn ($v) => (bool) $v['halal']);
        }

        if ($type === 'corporate') {
            if ($cat === 'venue' || $cat === 'eo') {
                $f[] = self::flag('av', 'AV / produksi in-house', 'Syarat', fn ($v) => (bool) $v['av']);
            }
            if ($cat === 'venue') {
                $f[] = self::flag('bus', 'Parkir bus', 'Syarat', fn ($v) => $v['bus'] >= 3);
            }
            if ($cat === 'eo') {
                $f[] = self::flag('bus', 'Venue rekanan dengan parkir bus', 'Syarat', fn ($v) => $v['bus'] > 0);
            }
            if ($cat === 'catering') {
                $f[] = self::flag('halal', 'Halal bersertifikat', 'Syarat', fn ($v) => (bool) $v['halal']);
            }
            $f[] = self::flag('pkp', 'Faktur pajak (PKP)', 'Syarat', fn ($v) => (bool) $v['pkp']);
        }

        return $f;
    }

    /** Buang kunci yang tidak dikenal filter halaman ini dan nilai yang tidak sah. */
    public static function normalize(array $defs, array $q): array
    {
        $out = [];
        foreach ($defs as $d) {
            $val = $q[$d['key']] ?? null;
            if ($d['kind'] === 'flag' && ($val === '1' || $val === 1 || $val === true)) {
                $out[$d['key']] = '1';
            } elseif ($d['kind'] === 'band' && is_string($val) && self::find($d['options'], $val)) {
                $out[$d['key']] = $val;
            }
        }

        return $out;
    }

    public static function active(array $defs, array $q): array
    {
        return array_values(array_filter($defs, fn ($d) => isset(self::normalize([$d], $q)[$d['key']])));
    }

    public static function apply(array $vendors, array $defs, array $q): array
    {
        $act = self::active($defs, $q);

        return array_values(array_filter($vendors, function ($v) use ($act, $q) {
            foreach ($act as $d) {
                if (! ($d['test'])($v, (string) $q[$d['key']])) {
                    return false;
                }
            }

            return true;
        }));
    }

    public static function valueLabel(array $d, array $q): string
    {
        if ($d['kind'] === 'flag') {
            return $d['label'];
        }

        return self::find($d['options'], $q[$d['key']] ?? null)['label'] ?? '';
    }

    public static function chipLabel(array $d, array $q): string
    {
        return $d['kind'] === 'flag' || ! empty($d['plain'])
            ? self::valueLabel($d, $q)
            : $d['label'].': '.self::valueLabel($d, $q);
    }

    public static function sort(array &$list, string $sort): void
    {
        match ($sort) {
            'harga' => usort($list, fn ($a, $b) => $a['price'] <=> $b['price']),
            'kapasitas' => usort($list, fn ($a, $b) => ($b['cap'][1] ?? 0) <=> ($a['cap'][1] ?? 0)),
            default => Catalog::editorialSort($list),
        };
    }

    /** Definisi tanpa closure, untuk dikirim ke view / props. */
    public static function public(array $defs, array $q): array
    {
        return array_map(fn ($d) => [
            'key' => $d['key'],
            'label' => $d['label'],
            'kind' => $d['kind'],
            'group' => $d['group'] ?? null,
            'options' => array_map(fn ($o) => ['id' => $o['id'], 'label' => $o['label']], $d['options'] ?? []),
            'value' => $q[$d['key']] ?? '',
        ], $defs);
    }
}
