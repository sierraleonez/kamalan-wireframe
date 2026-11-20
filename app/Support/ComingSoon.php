<?php

namespace App\Support;

use App\Catalog\Catalog;

/**
 * Mode "Segera hadir": pengunjung melihat satu layar pengumuman di semua halaman; admin yang masuk
 * tetap melihat situs biasa. Diatur di admin → Konten → Segera hadir, disimpan di setting 'site.coming_soon'.
 */
final class ComingSoon
{
    public const DEFAULTS = [
        'enabled' => false,
        'title' => 'Segera hadir',
        'text' => 'Kami sedang menyiapkan Kamalan Event Hub: venue dan vendor acara di Jabodetabek yang kami datangi sendiri. Sampai jumpa sebentar lagi.',
        'launch' => '',
        'show_contact' => true,
        'show_photo' => true,
    ];

    /** @param  iterable<string, ?string>  $settings */
    public static function fromSettings($settings): array
    {
        $raw = json_decode($settings['site.coming_soon'] ?? '[]', true) ?: [];

        return array_merge(self::DEFAULTS, array_intersect_key($raw, self::DEFAULTS));
    }

    public static function config(): array
    {
        return Catalog::data()['comingSoon'] ?? self::DEFAULTS;
    }

    public static function active(): bool
    {
        return (bool) self::config()['enabled'];
    }
}
