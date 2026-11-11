<?php

namespace App\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Isi katalog sebagai baris tabel apa adanya (dengan id), untuk database/data/catalog.json:
 * `php artisan catalog:export` menulisnya dari database, CatalogSeeder memuatnya di instalasi baru.
 */
final class CatalogData
{
    /** Urutan tabel mengikuti foreign key (induk dulu). Akun, permintaan, dan pendaftaran vendor tidak ikut. */
    public const TABLES = [
        'occasions', 'categories', 'category_occasion', 'areas',
        'vendors', 'occasion_vendor', 'vendor_photos',
        'bundles', 'bundle_members', 'collections', 'collection_picks',
        'settings',
    ];

    public static function path(): string
    {
        return database_path('data/catalog.json');
    }

    /** @return array<string, list<array<string, mixed>>> */
    public static function export(): array
    {
        $out = [];
        foreach (self::TABLES as $table) {
            $q = DB::table($table);
            foreach (Schema::hasColumn($table, 'id') ? ['id'] : Schema::getColumnListing($table) as $col) {
                $q->orderBy($col);
            }
            $out[$table] = $q->get()->map(fn ($row) => (array) $row)->all();
        }

        return $out;
    }

    /** Masukkan baris hasil export() ke database yang tabelnya masih kosong. */
    public static function import(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach (self::TABLES as $table) {
                foreach (array_chunk($data[$table] ?? [], 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        });
        Catalog::flush();
    }
}
