<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Foto hero beranda (carousel), diatur di admin → Konten beranda. Disimpan di setting 'home.hero_slides'
 * sebagai JSON [{image, alt}, …] berurutan; 'home.hero_interval' = detik per foto. Situs lama yang baru
 * punya satu foto (home.hero_image / home.hero_alt) memakainya sebagai foto pertama.
 */
final class HeroSlides
{
    public const DEFAULT_INTERVAL = 5;

    public const MAX = 8;

    /** @param  iterable<string, ?string>  $settings  kunci → nilai, mis. hasil pluck('value', 'key')
     * @return list<array{image: string, alt: string}> */
    public static function fromSettings($settings): array
    {
        $slides = json_decode($settings['home.hero_slides'] ?? 'null', true);
        if (! is_array($slides)) {
            $slides = ($settings['home.hero_image'] ?? null) ? [['image' => $settings['home.hero_image'], 'alt' => $settings['home.hero_alt'] ?? '']] : [];
        }

        return array_values(array_filter(array_map(
            fn ($s) => ['image' => (string) ($s['image'] ?? ''), 'alt' => (string) ($s['alt'] ?? '')], $slides,
        ), fn ($s) => $s['image'] !== ''));
    }

    public static function stored(): array
    {
        return self::fromSettings(Setting::query()->pluck('value', 'key'));
    }

    public static function interval($settings): int
    {
        $n = (int) ($settings['home.hero_interval'] ?? self::DEFAULT_INTERVAL);

        return $n >= 2 && $n <= 30 ? $n : self::DEFAULT_INTERVAL;
    }
}
