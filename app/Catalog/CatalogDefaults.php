<?php

namespace App\Catalog;

use App\Models\Category;
use App\Models\CategoryOccasion;
use App\Models\Occasion;
use App\Models\Vendor;

/** Perubahan data bawaan yang dipakai bersama oleh migrasi (situs berjalan) dan seeder (instalasi baru). */
final class CatalogDefaults
{
    /**
     * AV / produksi tampil di semua jenis acara, dan semua vendor AV melayani semuanya.
     * Judul H1 mengikuti judul venue jenis acara itu: "Venue pernikahan" → "AV dan produksi pernikahan".
     */
    public static function avEverywhere(): void
    {
        $av = Category::where('slug', 'av-produksi')->first();
        $venue = Category::where('slug', 'venue')->first();
        if (! $av || ! $venue) {
            return;
        }

        $occasions = Occasion::orderBy('sort')->get();
        foreach ($occasions as $occ) {
            if (CategoryOccasion::where(['occasion_id' => $occ->id, 'category_id' => $av->id])->exists()) {
                continue;
            }
            $venueH1 = CategoryOccasion::where(['occasion_id' => $occ->id, 'category_id' => $venue->id])->value('h1');
            CategoryOccasion::create([
                'occasion_id' => $occ->id,
                'category_id' => $av->id,
                'h1' => 'AV dan produksi '.($venueH1 ? preg_replace('/^Venue\s+/u', '', $venueH1) : mb_strtolower($occ->name)),
                'sort' => (int) CategoryOccasion::where('occasion_id', $occ->id)->max('sort') + 1,
            ]);
        }

        foreach (Vendor::where('category_id', $av->id)->get() as $vendor) {
            $vendor->occasions()->syncWithoutDetaching($occasions->pluck('id'));
        }

        Catalog::flush();
    }
}
