<?php

namespace App\Catalog;

/**
 * Teks beranda yang bisa diubah di admin → Konten beranda. Yang diubah disimpan di setting
 * 'home.texts' (JSON kunci → teks); kotak yang dikosongkan kembali ke teks bawaan di sini.
 */
final class HomeText
{
    /** Kelompok (judul di admin) → kunci → [label, teks bawaan, banyak baris?]. */
    public const FIELDS = [
        'Hero' => [
            'hero_eyebrow' => ['Teks kecil di atas judul', 'People · Ideas · Places · Possibilities', false],
            'hero_title' => ['Judul (Enter = pindah baris)', "Vendor Terpercaya,\nAcara Lebih Tenang.", true],
            'hero_lead' => ['Paragraf', 'Venue, catering, EO, dan hiburan di Jabodetabek — semuanya kami datangi sendiri sebelum masuk daftar. Harga dan kapasitas apa adanya.', true],
            'hero_button' => ['Tombol', 'Mulai jelajahi →', false],
            'hero_script' => ['Tulisan di foto (Enter = pindah baris)', "Momen yang\nlebih berarti", true],
        ],
        'Jenis acara' => [
            'occasions_title' => ['Judul', 'Pilih Acaramu', false],
        ],
        'Kategori' => [
            'cats_eyebrow' => ['Teks kecil', 'Kategori', false],
            'cats_title' => ['Judul', 'Kebutuhan Acaramu', false],
        ],
        'Paket bundle' => [
            'bundles_eyebrow' => ['Teks kecil', 'Best Deals', false],
            'bundles_title' => ['Judul', 'Nggak Perlu Hubungi Vendor Satu per Satu', false],
            'bundles_intro' => ['Paragraf', 'Venue, dekorasi, catering, dan kebutuhan lainnya bisa dikoordinasikan melalui satu EO dalam paket yang lebih praktis.', true],
            'bundles_more' => ['Tautan', 'Semua bundle →', false],
        ],
        'Vendor' => [
            'vendors_eyebrow' => ['Teks kecil', 'Vendor pilihan', false],
            'vendors_title' => ['Judul', 'Vendor Terbaik', false],
            'vendors_more' => ['Tautan', 'Semua vendor →', false],
        ],
        'Koleksi' => [
            'collections_eyebrow' => ['Teks kecil', 'Koleksi', false],
            'collections_title' => ['Judul', 'Sudah Kami Kurasi', false],
            'collections_more' => ['Tautan', 'Semua koleksi →', false],
        ],
        'Ajakan untuk vendor' => [
            'band_title' => ['Judul', 'Punya usaha vendor acara?', false],
            'band_text' => ['Paragraf', 'Daftarkan usahamu di Kamalan. Tim kami menghubungi lewat WhatsApp untuk kenalan, lalu datang langsung sebelum usahamu masuk katalog.', true],
            'band_button' => ['Tombol', 'Daftarkan usaha →', false],
        ],
    ];

    /** @return array<string, string> kunci → teks bawaan */
    public static function defaults(): array
    {
        return array_merge(...array_map(fn ($group) => array_map(fn ($f) => $f[1], $group), array_values(self::FIELDS)));
    }

    /** Teks yang tampil: ubahan admin, atau bawaan kalau tidak diubah. */
    public static function all(): array
    {
        return array_merge(self::defaults(), array_intersect_key(Catalog::data()['texts'] ?? [], self::defaults()));
    }

    /** Ubahan untuk disimpan: hanya yang diisi dan berbeda dari bawaan. */
    public static function overrides(array $state): array
    {
        $out = [];
        foreach (self::defaults() as $key => $default) {
            $value = trim(str_replace("\r\n", "\n", (string) ($state[$key] ?? '')));
            if ($value !== '' && $value !== $default) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
