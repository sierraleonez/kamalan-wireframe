<?php

namespace App\Catalog;

use App\Catalog\Present as P;
use App\Models\Vendor;

/**
 * Mengisi teks halaman vendor (deskripsi, catatan harga, yang termasuk, FAQ, panel per jenis acara)
 * dan rincian kategori dari template, supaya tim mengedit teks yang benar-benar tampil.
 * Hanya kolom yang masih null yang diisi; teks yang sengaja dikosongkan tim tidak disentuh.
 */
final class VendorText
{
    /** Isi semua vendor (migrasi, seeder). */
    public static function fillAll(): int
    {
        Catalog::flush();
        $rows = Catalog::build()['vendors'];
        $n = 0;
        foreach (Vendor::all() as $vendor) {
            if (isset($rows[$vendor->slug]) && self::apply($vendor, $rows[$vendor->slug], false)) {
                $vendor->saveQuietly();
                $n++;
            }
        }
        Catalog::flush();

        return $n;
    }

    /** Setelah vendor dan jenis acaranya tersimpan (admin): isi yang masih kosong. */
    public static function fill(Vendor $vendor): void
    {
        self::run($vendor, false);
    }

    /** Tulis ulang semua teks dari rincian terbaru. */
    public static function regenerate(Vendor $vendor): void
    {
        self::run($vendor, true);
    }

    private static function run(Vendor $vendor, bool $overwrite): void
    {
        Catalog::flush();
        $row = Catalog::build()['vendors'][$vendor->slug] ?? null;
        if ($row && self::apply($vendor, $row, $overwrite)) {
            $vendor->save();
        }
    }

    /** @param  array  $v  vendor dalam bentuk Catalog */
    private static function apply(Vendor $vendor, array $v, bool $overwrite): bool
    {
        $specs = array_replace(Specs::filled($v), array_filter($vendor->specs ?? [], fn ($x) => $x !== null));
        $vendor->specs = $specs;
        $v['specs'] = $specs;
        $empty = fn (string $col) => $overwrite || $vendor->{$col} === null;

        if ($empty('writeup')) {
            $vendor->writeup = implode("\n\n", Copy::writeup(['writeup' => null] + $v));
        }
        if ($empty('price_note')) {
            $vendor->price_note = P::priceNote($v);
        }
        if ($empty('included')) {
            $vendor->included = array_map(fn ($i) => ['label' => $i[0], 'note' => $i[1]], Copy::included($v));
        }
        if ($empty('faqs')) {
            $vendor->faqs = Copy::faqs($v);
        }

        $notes = $overwrite ? [] : ($vendor->occasion_notes ?? []);
        $have = array_column($notes, 'occasion');
        foreach ($v['types'] as $occ) {
            if (in_array($occ, $have, true)) {
                continue;
            }
            $note = Copy::occasionNote($v, $occ);
            if ($note['paras']) {
                $notes[] = ['occasion' => $occ, 'title' => $note['title'], 'text' => implode("\n\n", $note['paras'])];
            }
        }
        $vendor->occasion_notes = $notes;

        return $vendor->isDirty();
    }

    /** Teks berparagraf (dipisah baris kosong) menjadi daftar paragraf. */
    public static function paras(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R\s*\R/', trim((string) $text)))));
    }
}
