<?php

namespace App\Support;

/**
 * Kontak Kamalan di footer ("Hubungi kami"), diatur di admin → Konten beranda.
 * Disimpan di setting 'site.contact' sebagai JSON {wa, email, ig, address}; yang kosong tidak tampil.
 */
final class SiteContact
{
    public const FIELDS = ['wa', 'email', 'ig', 'address'];

    /** @return array{wa: string, email: string, ig: string, address: string} */
    public static function fromSettings($settings): array
    {
        $raw = json_decode($settings['site.contact'] ?? '[]', true) ?: [];

        return array_map(fn ($k) => trim((string) ($raw[$k] ?? '')), array_combine(self::FIELDS, self::FIELDS));
    }

    /** Baris yang tampil di footer: [label, href|null]. */
    public static function links(array $c): array
    {
        $digits = preg_replace('/\D/', '', $c['wa']);
        $ig = ltrim(preg_replace('#^https?://(www\.)?instagram\.com/#i', '', $c['ig']), '@/');

        return array_values(array_filter([
            $digits !== '' ? ['WhatsApp '.$c['wa'], 'https://wa.me/'.preg_replace('/^0/', '62', $digits)] : null,
            $c['email'] !== '' ? [$c['email'], 'mailto:'.$c['email']] : null,
            $ig !== '' ? ['Instagram @'.rtrim($ig, '/'), 'https://instagram.com/'.rtrim($ig, '/')] : null,
            $c['address'] !== '' ? [$c['address'], null] : null,
        ]));
    }
}
