<?php

namespace App\Catalog;

/**
 * Format tampilan dan model kartu. Semua teks siap-tampil dibentuk di sini,
 * view Blade hanya merender.
 */
final class Present
{
    public static function num(int|float $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    public static function guestWord(string $type): string
    {
        return $type === 'corporate' ? 'peserta' : 'pax';
    }

    private static function unitSuffix(string $cat): string
    {
        return match (Catalog::category($cat)['unit']) {
            'pax' => ' rb/pax',
            'hari' => ' jt/hari',
            'acara' => ' jt/acara',
            default => ' jt',
        };
    }

    public static function priceFrom(array $v): string
    {
        return $v['price'].self::unitSuffix($v['cat']);
    }

    public static function priceRange(array $v): string
    {
        $hi = Catalog::category($v['cat'])['unit'] === 'pax'
            ? (int) (round($v['price'] * 1.5 / 5) * 5)
            : (int) round($v['price'] * 1.45);

        return $v['price'].' – '.$hi.self::unitSuffix($v['cat']);
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

    public static function extras(array $v, string $type): array
    {
        $e = [];
        if ($v['cat'] === 'venue') {
            if ($type === 'wedding') {
                $e[] = self::settingText($v['setting']);
                if ($v['cateringBebas']) {
                    $e[] = 'catering bebas';
                }
            } else {
                if ($v['av']) {
                    $e[] = 'AV in-house';
                }
                if ($v['bus'] >= 3) {
                    $e[] = 'parkir '.$v['bus'].' bus';
                }
                if ($v['pkp']) {
                    $e[] = 'PKP';
                }
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
        return $b['perHead'] ? 'Rp '.$b['priceMin'].' rb/peserta' : $b['priceMin'].'–'.$b['priceMax'].' jt';
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

    public static function waMessage(string $name, string $type, string $ref, bool $bundle = false): string
    {
        return 'Halo, saya lihat '.$name.' di EventHub. Mau tanya untuk '
            .($type === 'corporate' ? 'acara kantor' : 'pernikahan').($bundle ? ' (paket)' : '').'. (ref: '.$ref.')';
    }

    public static function waUrl(string $number, string $text): string
    {
        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }

    /** Tautan internal yang meneruskan ke wa.me (tempat pencatatan klik nanti). */
    public static function goHref(string $ref, string $type): string
    {
        return '/go/'.$ref.'?t='.$type;
    }

    /* ---------- kartu ---------- */

    public static function savedKey(string $kind, string $type, string $slug): string
    {
        return $kind.':'.$type.':'.$slug;
    }

    public static function vendorCard(array $v, string $type, bool $promo = false): array
    {
        return [
            'key' => self::savedKey('v', $type, $v['slug']),
            'name' => $v['name'],
            'href' => self::vendorPath($v, $type),
            'photo' => 'foto '.mb_strtolower(Catalog::category($v['cat'])['name']),
            'meta' => implode(' · ', array_filter([$v['hood'], self::capText($v, $type)])),
            'meta2' => implode(' · ', array_merge(['Mulai '.self::priceFrom($v)], self::extras($v, $type))),
            'wa' => self::goHref($v['ref'], $type),
            'ref' => $v['ref'],
            'promo' => $promo,
        ];
    }

    public static function bundleCard(array $b, bool $promo = false): array
    {
        $eo = Catalog::vendor($b['eo'] ?? '');
        $parts = implode(' + ', array_map(fn ($m) => str_replace(' / produksi', '', Catalog::category($m[0])['name']), $b['members']));

        return [
            'key' => self::savedKey('b', $b['type'], $b['slug']),
            'name' => $b['title'],
            'href' => self::bundlePath($b),
            'photo' => 'foto bundle',
            'meta' => $parts,
            'meta2' => self::bundlePrice($b).($b['perHead'] ? ' · min. '.$b['minGuests'] : '').' · '.Catalog::area($b['area'])['short'].($eo ? ' · oleh '.$eo['name'] : ''),
            'wa' => self::goHref($b['ref'], $b['type']),
            'ref' => $b['ref'],
            'promo' => $promo,
        ];
    }

    public static function collectionTile(array $c): array
    {
        return ['href' => '/koleksi/'.$c['slug'], 'label' => $c['short'], 'sponsor' => $c['sponsor'] ?? null];
    }

    public static function crumb(array $parts): array
    {
        return array_map(fn ($p) => ['label' => $p[0], 'href' => $p[1] ?? null], $parts);
    }
}
