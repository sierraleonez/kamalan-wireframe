<?php

namespace App\Catalog;

use App\Catalog\Present as P;

/**
 * Rincian vendor per kategori (kolom JSON vendors.specs). Nilai default sama dengan teks yang
 * dulu tertulis di kode (sewa 6 jam, DP 30%, …), jadi vendor lama tampil sama setelah diisi.
 */
final class Specs
{
    /** @return array<string, array{label:string, unit?:string, default:int|float|null}> per kategori */
    public static function fields(string $cat): array
    {
        $duration = [
            'duration_min' => ['label' => 'Durasi standar dari', 'unit' => 'jam', 'default' => 3],
            'duration_max' => ['label' => 'Durasi standar sampai', 'unit' => 'jam', 'default' => 4],
        ];

        return match ($cat) {
            'venue' => [
                'duration_hours' => ['label' => 'Lama sewa', 'unit' => 'jam', 'default' => 6],
                'overtime' => ['label' => 'Biaya overtime', 'unit' => 'juta / jam', 'default' => null], // dihitung dari harga
                'corkage' => ['label' => 'Biaya bawa catering sendiri', 'unit' => 'ribu / pax', 'default' => 25],
            ],
            'catering' => [
                'waiter_ratio' => ['label' => 'Satu pramusaji untuk setiap', 'unit' => 'tamu', 'default' => 25],
                'tasting_people' => ['label' => 'Tes rasa gratis untuk', 'unit' => 'orang', 'default' => 2],
            ],
            'eo' => [
                'meetings' => ['label' => 'Rapat teknis sebelum hari-H', 'unit' => 'kali', 'default' => 2],
                'crew_min' => ['label' => 'Kru hari-H dari', 'unit' => 'orang', 'default' => 4],
                'crew_max' => ['label' => 'Kru hari-H sampai', 'unit' => 'orang', 'default' => 10],
            ],
            'dekorasi' => $duration + [
                'setup_hours' => ['label' => 'Waktu pasang', 'unit' => 'jam', 'default' => null],
            ],
            'dokumentasi' => $duration + [
                'delivery_days' => ['label' => 'Hasil jadi dalam', 'unit' => 'hari', 'default' => null],
            ],
            default => $duration, // hiburan, av-produksi
        } + [
            'dp_percent' => ['label' => 'DP', 'unit' => '%', 'default' => 30],
        ];
    }

    /** Nilai rincian vendor, atau default kategorinya. */
    public static function get(array $v, string $key): int|float|null
    {
        $value = $v['specs'][$key] ?? null;
        if ($value !== null) {
            return $value;
        }
        if ($key === 'overtime') {
            return round($v['price'] * 0.8) / 10;
        }

        return self::fields($v['cat'])[$key]['default'] ?? null;
    }

    /** Rincian lengkap untuk disimpan: nilai yang ada + default untuk yang belum diisi. */
    public static function filled(array $v): array
    {
        $out = [];
        foreach (array_keys(self::fields($v['cat'])) as $key) {
            $out[$key] = self::get($v, $key);
        }

        return $out;
    }

    /** "3–4 jam", atau "3 jam" kalau sama. */
    public static function range(array $v, string $min, string $max, string $unit): string
    {
        $a = self::get($v, $min);
        $b = self::get($v, $max);

        return P::num($a).($b !== null && $b != $a ? '–'.P::num($b) : '').' '.$unit;
    }
}
