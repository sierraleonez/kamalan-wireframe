<?php

namespace App\Catalog;

/**
 * Format tampilan dan model kartu. Semua teks siap-tampil dibentuk di sini,
 * view Blade hanya merender.
 */
final class Present
{
    /** Penempatan tombol WhatsApp, ikut di kode referral: VNU-0104-DETAIL. */
    public const PLACEMENTS = ['HOME', 'LIST', 'DETAIL', 'KOLEKSI', 'SAVED', 'BUNDLE'];

    public static function num(int|float $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    public static function guestWord(string $type): string
    {
        return $type === 'corporate' ? 'peserta' : 'pax';
    }

    public static function unit(array $v): string
    {
        return Catalog::category($v['cat'])['unit'];
    }

    /** Harga singkat untuk kartu: "Rp 45 jt", "Rp 95 rb/pax". */
    public static function priceFrom(array $v): string
    {
        return 'Rp '.$v['price'].match (self::unit($v)) {
            'pax' => ' rb/pax',
            'hari' => ' jt/hari',
            'acara' => ' jt/acara',
            default => ' jt',
        };
    }

    /** Harga panjang untuk kotak CTA: "Rp 45 juta", "Rp 95 rb / pax". */
    public static function priceLong(array $v): string
    {
        return 'Rp '.$v['price'].match (self::unit($v)) {
            'pax' => ' rb / pax',
            'hari' => ' juta / hari',
            'acara' => ' juta / acara',
            default => ' juta',
        };
    }

    /** Yang belum termasuk: harga tidak pernah tampil tanpa pengecualiannya. */
    public static function priceNote(array $v): string
    {
        $area = Catalog::area($v['area'])['name'];

        return match ($v['cat']) {
            'venue' => $v['cateringBebas']
                ? 'Sewa venue 6 jam, belum termasuk catering dan dekorasi'
                : 'Sewa venue 6 jam, belum termasuk dekorasi; catering dari rekanan dihitung terpisah',
            'catering' => 'Per pax untuk menu standar, belum termasuk stall tambahan',
            'eo' => 'Jasa koordinasi, belum termasuk venue dan vendor lain',
            default => 'Harga dasar, belum termasuk transport di luar '.$area,
        };
    }

    public static function capText(array $v, string $type): string
    {
        if (empty($v['cap'])) {
            return '';
        }

        return match ($v['cat']) {
            'venue' => self::num($v['cap'][0]).'–'.self::num($v['cap'][1]).' '.self::guestWord($type),
            'catering' => 'min. '.self::num($v['cap'][0]).' pax',
            'eo' => 's.d. '.self::num($v['cap'][1]).' '.($type === 'corporate' ? 'peserta' : 'tamu'),
            default => '',
        };
    }

    public static function settingText(?string $s): string
    {
        return match ($s) {
            'indoor' => 'Indoor',
            'outdoor' => 'Outdoor',
            default => 'Indoor + outdoor',
        };
    }

    /** Batasan yang dicari pembeli corporate, dan fakta pendek lain untuk meta kartu. */
    public static function extras(array $v, string $type): array
    {
        $e = [];
        if ($v['cat'] === 'venue') {
            if ($type === 'corporate') {
                if ($v['av']) {
                    $e[] = 'AV in-house';
                }
                if ($v['bus'] >= 3) {
                    $e[] = 'parkir '.$v['bus'].' bus';
                }
                if ($v['pkp']) {
                    $e[] = 'PKP';
                }
            } elseif ($v['cateringBebas']) {
                $e[] = 'catering bebas';
            }
        } elseif ($v['cat'] === 'eo' && $type === 'corporate') {
            $e[] = $v['av'] ? 'AV in-house' : 'AV sewa';
            $e[] = $v['pkp'] ? 'PKP' : 'non-PKP';
        } elseif ($v['cat'] === 'catering' && $v['halal']) {
            $e[] = 'halal';
        }

        return $e;
    }

    public static function bundlePrice(array $b): string
    {
        return $b['perHead'] ? 'Rp '.$b['priceMin'].' rb / peserta' : 'Rp '.$b['priceMin'].' – '.$b['priceMax'].' jt';
    }

    /** Gradien placeholder foto, stabil per slug. */
    public static function tone(string $slug, string $set = 't', int $n = 5): string
    {
        return $set.(crc32($slug) % $n + 1);
    }

    /* ---------- path ---------- */

    public static function vendorPath(array $v, string $type): string
    {
        return '/'.$type.'/'.$v['cat'].'/'.$v['slug'];
    }

    public static function bundlePath(array $b): string
    {
        return '/'.$b['type'].'/bundle/'.$b['slug'];
    }

    public static function listingPath(string $type, string $cat, ?string $area = null, array $q = []): string
    {
        $q = array_filter($q, fn ($v) => $v !== null && $v !== '');
        $qs = http_build_query($q, '', '&', PHP_QUERY_RFC3986);

        return '/'.$type.'/'.$cat.($area ? '/'.$area : '').($qs !== '' ? '?'.$qs : '');
    }

    /* ---------- WhatsApp ---------- */

    public static function refCode(string $ref, string $placement): string
    {
        return $ref.'-'.(in_array($placement, self::PLACEMENTS, true) ? $placement : 'LIST');
    }

    public static function waMessage(string $name, string $type, string $ref, string $placement): string
    {
        return 'Halo, saya dari Kamalan — ref '.self::refCode($ref, $placement).'. Saya lihat '.$name.' untuk '.Catalog::type($type)['phrase'].'.';
    }

    public static function waUrl(string $number, string $text): string
    {
        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }

    /** Tautan internal yang meneruskan ke wa.me (tempat pencatatan klik nanti). */
    public static function goHref(string $ref, string $type, string $placement): string
    {
        return '/go/'.$ref.'?t='.$type.'&p='.$placement;
    }

    /* ---------- kartu ---------- */

    public static function savedKey(string $kind, string $type, string $slug): string
    {
        return $kind.':'.$type.':'.$slug;
    }

    public static function vendorCard(array $v, string $type, bool $promo = false, string $placement = 'LIST'): array
    {
        return [
            'key' => self::savedKey('v', $type, $v['slug']),
            'name' => $v['name'],
            'href' => self::vendorPath($v, $type),
            'tone' => self::tone($v['slug']),
            'chip' => $v['cat'] === 'venue' ? self::settingText($v['setting']) : Catalog::category($v['cat'])['name'],
            'meta' => implode(' · ', array_filter(array_merge([$v['hood'], self::capText($v, $type)], self::extras($v, $type)))),
            // Tanpa chip foto (kartu "serupa" di halaman detail), suasana pindah ke meta.
            'metaNoChip' => implode(' · ', array_filter(array_merge([$v['hood'], self::capText($v, $type), $v['cat'] === 'venue' ? self::settingText($v['setting']) : null], self::extras($v, $type)))),
            'price' => 'Mulai '.self::priceFrom($v),
            'wa' => self::goHref($v['ref'], $type, $placement),
            'cta' => 'WhatsApp',
            'promo' => $promo,
        ];
    }

    public static function bundleCard(array $b, bool $promo = false, string $placement = 'LIST'): array
    {
        $eo = Catalog::vendor($b['eo'] ?? '');
        $parts = implode(' + ', array_map(fn ($m) => $m[0] === 'av-produksi' ? 'AV' : mb_strtolower(Catalog::category($m[0])['name']), $b['members']));

        return [
            'key' => self::savedKey('b', $b['type'], $b['slug']),
            'name' => $b['title'],
            'href' => self::bundlePath($b),
            'tone' => self::tone($b['slug']),
            'chip' => null,
            'meta' => ($eo ? 'oleh '.$eo['name'].' · ' : '').$parts,
            'metaNoChip' => ($eo ? 'oleh '.$eo['name'].' · ' : '').$parts,
            'price' => self::bundlePrice($b).($b['perHead'] ? ' · min. '.$b['minGuests'] : ''),
            'wa' => self::goHref($b['ref'], $b['type'], $placement),
            'cta' => 'Hubungi EO',
            'promo' => $promo,
        ];
    }

    public static function collectionTile(array $c): array
    {
        return ['href' => '/koleksi/'.$c['slug'], 'label' => $c['short'], 'sponsor' => $c['sponsor'] ?? null, 'tone' => self::tone($c['slug'], 'k', 3)];
    }

    public static function crumb(array $parts): array
    {
        return array_map(fn ($p) => ['label' => $p[0], 'href' => $p[1] ?? null], $parts);
    }
}
