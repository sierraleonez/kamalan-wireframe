<?php

namespace App\Catalog;

use App\Catalog\Present as P;

/**
 * Props siap-tampil per halaman, dirender oleh view Blade di resources/views/pages.
 */
final class Pages
{
    public const PER_PAGE = 9;

    private static function base(string $title, string $notes, array $extra = []): array
    {
        return ['title' => $title, 'notes' => Copy::NOTES[$notes] ?? [], 'notesKey' => $notes] + $extra;
    }

    /* ---------------- beranda ---------------- */

    public static function catRow(?string $type): array
    {
        $cats = $type ? Catalog::type($type)['cats'] : ['venue', 'catering', 'eo', 'hiburan', 'dekorasi', 'dokumentasi'];
        $w = Catalog::type('wedding')['cats'];
        $c = Catalog::type('corporate')['cats'];

        return array_map(function ($cat) use ($type, $w, $c) {
            $name = Catalog::category($cat)['name'];
            if ($type) {
                return ['name' => $name, 'href' => '/'.$type.'/'.$cat, 'choices' => []];
            }
            $inW = in_array($cat, $w, true);
            $inC = in_array($cat, $c, true);
            if (! ($inW && $inC)) {
                return ['name' => $name, 'href' => '/'.($inW ? 'wedding' : 'corporate').'/'.$cat, 'choices' => []];
            }

            return ['name' => $name, 'href' => null, 'choices' => [
                ['label' => 'Wedding', 'href' => '/wedding/'.$cat],
                ['label' => 'Corporate', 'href' => '/corporate/'.$cat],
            ]];
        }, $cats);
    }

    public static function home(): array
    {
        $bundles = array_map(fn ($s) => Catalog::bundle($s), ['paket-intimate-wedding-150-pax', 'gathering-kantor-300-peserta', 'akad-resepsi-500-pax']);
        $vendors = array_filter([
            [Catalog::vendor('ballroom-kebayoran'), true],
            [Catalog::firstEditorial('wedding', 'catering', 'jakarta-timur'), false],
            [Catalog::firstEditorial('wedding', 'eo'), false],
            [Catalog::firstEditorial('wedding', 'venue', 'bekasi'), false],
        ], fn ($x) => $x[0] !== null);

        return self::base('Vendor acara Jabodetabek', 'home', [
            'tiles' => array_map(fn ($t) => ['name' => Catalog::type($t)['name'], 'href' => '/'.$t, 'blurb' => Catalog::type($t)['blurb']], ['wedding', 'corporate']),
            'cats' => self::catRow(null),
            'bundles' => array_map(fn ($b) => P::bundleCard($b, ! empty($b['promoted'])), $bundles),
            'vendors' => array_values(array_map(fn ($x) => P::vendorCard($x[0], 'wedding', $x[1]), $vendors)),
            'collections' => array_map(fn ($s) => P::collectionTile(Catalog::collection($s)), ['rooftop-jaksel-dibawah-50jt', 'venue-gathering-300-peserta-bsd', 'catering-halal-bersertifikat']),
            'bandHref' => '/kasih-tau-kami',
        ]);
    }

    public static function branch(string $type): array
    {
        $t = Catalog::type($type);
        $bundles = array_values(array_filter(Catalog::bundles(), fn ($b) => $b['type'] === $type));
        usort($bundles, fn ($a, $b) => (int) ! empty($b['promoted']) <=> (int) ! empty($a['promoted']));
        $venues = Catalog::vendorsOf($type, 'venue');
        Catalog::editorialSort($venues);
        $promo = null;
        foreach ($venues as $v) {
            if ($v['promoted']) {
                $promo = $v;
                break;
            }
        }
        $featured = array_values(array_filter(array_merge([$promo], array_map(fn ($c) => Catalog::firstEditorial($type, $c), array_slice($t['cats'], 1, 3)))));

        $areas = [];
        foreach (Catalog::areas() as $a) {
            $n = count(Catalog::vendorsOf($type, 'venue', $a['slug']));
            if ($n) {
                $areas[] = ['label' => $a['name'], 'href' => '/'.$type.'/venue/'.$a['slug'], 'count' => $n];
            }
        }

        return self::base($t['name'], 'branch', [
            'type' => $type,
            'crumb' => P::crumb([['Beranda', '/'], [$t['name']]]),
            'h1' => $type === 'corporate' ? 'Vendor acara kantor di Jabodetabek, dengan kapasitas, AV, dan faktur yang jelas.' : 'Vendor pernikahan di Jabodetabek, dengan harga yang kami tanyakan sendiri.',
            'lead' => $type === 'corporate' ? 'Gathering, seminar, town hall, dan peluncuran produk. Harga per peserta, kapasitas per layout, dan siapa yang bisa menerbitkan faktur pajak.' : 'Akad, resepsi, dan intimate wedding. Kapasitas kami hitung per layout, harga dari vendor bulan ini.',
            'cats' => self::catRow($type),
            'areas' => $areas,
            'bundlesSub' => $type === 'corporate' ? '— dihargai per peserta' : '',
            'bundles' => array_map(fn ($b) => P::bundleCard($b, ! empty($b['promoted'])), array_slice($bundles, 0, 3)),
            'vendors' => array_map(fn ($v, $i) => P::vendorCard($v, $type, $i === 0 && $v['promoted']), $featured, array_keys($featured)),
            'collections' => array_values(array_map(fn ($c) => P::collectionTile($c), array_slice(array_values(array_filter(Catalog::collections(), fn ($c) => $c['type'] === $type)), 0, 3))),
            'bandHref' => '/kasih-tau-kami?jenis='.$type,
        ]);
    }

    /* ---------------- listing ---------------- */

    /** Kueri yang sah untuk halaman ini, termasuk urutan. */
    public static function listingQuery(string $type, string $cat, array $raw): array
    {
        $q = Filters::normalize(Filters::defs($type, $cat), $raw);
        if (isset($raw['urut']) && isset(Filters::SORTS[$raw['urut']]) && $raw['urut'] !== 'editor') {
            $q['urut'] = $raw['urut'];
        }

        return $q;
    }

    public static function countFor(string $type, string $cat, ?string $area, array $q): int
    {
        return count(Filters::apply(Catalog::vendorsOf($type, $cat, $area), Filters::defs($type, $cat), $q));
    }

    public static function listing(string $type, string $cat, ?string $area, array $raw, int $shown = self::PER_PAGE): array
    {
        $defs = Filters::defs($type, $cat);
        $q = self::listingQuery($type, $cat, $raw);
        $sort = $q['urut'] ?? 'editor';
        $base = Catalog::vendorsOf($type, $cat, $area);
        $results = Filters::apply($base, $defs, $q);
        Filters::sort($results, $sort);

        $promo = null;
        foreach ($results as $i => $v) {
            if ($v['promoted']) {
                $promo = $v;
                array_splice($results, $i, 1);
                array_unshift($results, $v);
                break;
            }
        }

        $catName = Catalog::category($cat)['name'];
        $h1Noun = Catalog::category($cat)['h1'][$type];
        $where = $area ? Catalog::area($area)['name'] : 'Jabodetabek';
        $act = Filters::active($defs, $q);
        $total = count($results);
        $zero = $total === 0;
        $thin = $total > 0 && $total <= 3 && count($act) > 0;
        $filterQ = array_diff_key($q, ['urut' => 1]);

        $chips = [];
        if ($area) {
            $chips[] = ['label' => 'Area: '.$where, 'href' => P::listingPath($type, $cat, null, $q), 'key' => 'area'];
        }
        foreach ($act as $d) {
            $chips[] = ['label' => Filters::chipLabel($d, $q), 'href' => P::listingPath($type, $cat, $area, array_diff_key($q, [$d['key'] => 1])), 'key' => $d['key']];
        }

        $crumb = [['Beranda', '/'], [Catalog::type($type)['name'], '/'.$type], [$catName, $area ? '/'.$type.'/'.$cat : null]];
        if ($area) {
            $crumb[] = [$where];
        }

        $props = self::base($h1Noun.' di '.$where, 'listing', [
            'type' => $type,
            'cat' => $cat,
            'area' => $area,
            'crumb' => P::crumb($crumb),
            'h1' => $h1Noun.' di '.$where,
            'intro' => $zero ? null : Copy::listingIntro($type, $cat, $area, count($base)),
            'catNoun' => mb_strtolower($catName),
            'query' => $q,
            'basePath' => P::listingPath($type, $cat, $area),
            'areaOptions' => array_values(array_map(fn ($a) => ['value' => $a['slug'], 'label' => $a['name'], 'short' => $a['short'], 'href' => P::listingPath($type, $cat, $a['slug'], $q)], Catalog::areas())),
            'allAreasHref' => P::listingPath($type, $cat, null, $q),
            'filters' => Filters::public($defs, $q),
            'activeCount' => count($act) + ($area ? 1 : 0),
            'chips' => $chips,
            'resetHref' => P::listingPath($type, $cat, null, isset($q['urut']) ? ['urut' => $q['urut']] : []),
            'sort' => $sort,
            'sorts' => array_map(fn ($k, $l) => ['value' => $k, 'label' => $l], array_keys(Filters::SORTS), Filters::SORTS),
            'total' => $total,
            'shown' => min($shown, $total),
            'results' => array_map(fn ($v, $i) => P::vendorCard($v, $type, $i === 0 && $v === $promo), array_slice($results, 0, $shown), array_keys(array_slice($results, 0, $shown))),
            'zero' => $zero,
            'thin' => $thin,
            'zeroMessage' => $zero ? [
                'noun' => mb_strtolower($h1Noun),
                'summary' => implode(' · ', array_map(fn ($d) => mb_strtolower(Filters::chipLabel($d, $q)), $act)),
                'where' => $where,
            ] : null,
            'relaxations' => ($zero || $thin) ? self::relaxations($type, $cat, $area, $defs, $filterQ, $total, $q['urut'] ?? null) : [],
            'prefill' => ($zero || $thin) ? self::prefill($type, $cat, $area, $defs, $q) : null,
            'formOptions' => ($zero || $thin) ? self::formOptions() : null,
            'asal' => P::listingPath($type, $cat, $area, $q),
            'sideLinks' => $zero ? [] : self::sideLinks($type, $cat, $area, $defs),
            'bandHref' => '/kasih-tau-kami?'.http_build_query(array_filter(['jenis' => $type, 'kategori' => $cat, 'area' => $area])),
        ]);

        return $props;
    }

    public static function relaxations(string $type, string $cat, ?string $area, array $defs, array $q, int $current, ?string $sort = null): array
    {
        $out = [];
        $count = fn (?string $ar, array $qq) => count(Filters::apply(Catalog::vendorsOf($type, $cat, $ar), $defs, $qq));
        $keep = $sort ? ['urut' => $sort] : [];
        $act = Filters::active($defs, $q);

        foreach ($act as $d) {
            if (! empty($d['ordered'])) {
                $ids = array_column($d['options'], 'id');
                $i = array_search($q[$d['key']], $ids, true);
                if ($i !== false && $i < count($ids) - 1) {
                    $q2 = array_merge($q, [$d['key'] => $ids[$i + 1]]);
                    $out[] = ['pre' => $d['label'].' ', 'strong' => $d['options'][$i + 1]['label'], 'href' => P::listingPath($type, $cat, $area, $q2 + $keep), 'n' => $count($area, $q2)];
                }
            }
            $q3 = array_diff_key($q, [$d['key'] => 1]);
            $out[] = ['pre' => 'Hapus ', 'strong' => mb_strtolower(Filters::chipLabel($d, $q)), 'href' => P::listingPath($type, $cat, $area, $q3 + $keep), 'n' => $count($area, $q3)];
        }

        if ($area) {
            $sum = implode(', ', array_map(fn ($d) => Filters::valueLabel($d, $q), $act));
            foreach (Catalog::area($area)['near'] as $a2) {
                $out[] = ['pre' => $sum !== '' ? $sum.' di ' : 'Di ', 'strong' => Catalog::area($a2)['name'], 'href' => P::listingPath($type, $cat, $a2, $q + $keep), 'n' => $count($a2, $q)];
            }
        }

        $out = array_values(array_filter($out, fn ($o) => $o['n'] > $current));
        usort($out, fn ($a, $b) => $b['n'] <=> $a['n']);

        return array_slice($out, 0, 4);
    }

    private static function prefill(string $type, string $cat, ?string $area, array $defs, array $q): array
    {
        $pre = ['jenis' => $type, 'area' => $area ?? '', 'kategori' => $cat, 'tamu' => '', 'budget' => '', 'tanggal' => '', 'wa' => ''];
        foreach ($defs as $d) {
            if ($d['key'] === 'kapasitas' && isset($q['kapasitas'])) {
                $pre['tamu'] = Filters::valueLabel($d, $q);
            }
            if ($d['key'] === 'harga' && isset($q['harga'])) {
                $pre['budget'] = Filters::valueLabel($d, $q);
            }
        }
        $rest = array_map(fn ($d) => mb_strtolower(Filters::valueLabel($d, $q)), array_filter(Filters::active($defs, $q), fn ($d) => ! in_array($d['key'], ['kapasitas', 'harga'], true)));
        $pre['cari'] = Catalog::category($cat)['h1'][$type].($rest ? ', '.implode(', ', $rest) : '').'.';

        return $pre;
    }

    private static function sideLinks(string $type, string $cat, ?string $area, array $defs): array
    {
        $cols = [];
        $h1 = Catalog::category($cat)['h1'][$type];

        if ($area) {
            $links = [];
            foreach (Catalog::area($area)['near'] as $a) {
                $n = count(Catalog::vendorsOf($type, $cat, $a));
                if ($n) {
                    $links[] = ['label' => $h1.' di '.Catalog::area($a)['name'], 'href' => P::listingPath($type, $cat, $a), 'count' => $n];
                }
            }
            if ($links) {
                $cols[] = ['title' => 'Area terdekat', 'links' => $links];
            }
        } else {
            $links = [];
            foreach (array_slice(Catalog::areas(), 0, 5) as $a) {
                $n = count(Catalog::vendorsOf($type, $cat, $a['slug']));
                if ($n) {
                    $links[] = ['label' => $a['name'], 'href' => P::listingPath($type, $cat, $a['slug']), 'count' => $n];
                }
            }
            $cols[] = ['title' => 'Per area', 'links' => $links];
        }

        foreach ($defs as $d) {
            if ($d['key'] !== 'kapasitas') {
                continue;
            }
            $base = Catalog::vendorsOf($type, $cat, $area);
            $links = [];
            foreach ($d['options'] as $o) {
                $n = count(array_filter($base, fn ($v) => ($d['test'])($v, $o['id'])));
                if ($n) {
                    $links[] = ['label' => $o['label'].' '.P::guestWord($type), 'href' => P::listingPath($type, $cat, $area, ['kapasitas' => $o['id']]), 'count' => $n];
                }
            }
            $cols[] = ['title' => $type === 'corporate' ? 'Jumlah peserta lain' : 'Kapasitas lain', 'links' => $links];
        }

        $colls = array_values(array_filter(Catalog::collections(), fn ($c) => $c['type'] === $type && $c['cat'] === $cat));
        if ($colls) {
            $cols[] = ['title' => 'Koleksi terkait', 'links' => array_map(fn ($c) => ['label' => $c['short'], 'href' => '/koleksi/'.$c['slug'], 'count' => null], $colls)];
        }

        $others = array_slice(array_values(array_filter(Catalog::type($type)['cats'], fn ($c) => $c !== $cat)), 0, 4);
        $cols[] = [
            'title' => 'Kategori lain'.($area ? ' di '.Catalog::area($area)['short'] : ''),
            'links' => array_map(fn ($c) => ['label' => Catalog::category($c)['name'], 'href' => P::listingPath($type, $c, $area), 'count' => null], $others),
        ];

        return $cols;
    }

    /* ---------------- detail ---------------- */

    public static function detail(string $type, array $v): array
    {
        $cat = Catalog::category($v['cat']);
        $area = Catalog::area($v['area']);

        $tags = [];
        foreach ($v['types'] as $t) {
            $tags[] = $t === $type
                ? ['label' => Catalog::type($t)['name'], 'kind' => 'type-on', 'href' => null]
                : ['label' => Catalog::type($t)['name'].' →', 'kind' => 'type', 'href' => P::vendorPath($v, $t)];
        }
        $tags[] = ['label' => $v['hood'].', '.$area['short'], 'kind' => 'plain', 'href' => null];
        if ($v['cap'] && $v['cat'] === 'venue') {
            $tags[] = ['label' => P::capText($v, $type), 'kind' => 'plain', 'href' => null];
        }
        if ($v['setting']) {
            $tags[] = ['label' => P::settingText($v['setting']), 'kind' => 'plain', 'href' => null];
        }
        foreach (P::extras($v, $type) as $x) {
            if ($x !== P::settingText($v['setting'])) {
                $tags[] = ['label' => $x, 'kind' => 'plain', 'href' => null];
            }
        }

        $bundles = array_values(array_filter(Catalog::bundles(), function ($b) use ($type, $v) {
            if ($b['type'] !== $type) {
                return false;
            }

            return $b['eo'] === $v['slug'] || in_array($v['slug'], array_column($b['members'], 1), true);
        }));

        $similar = array_values(array_filter(Catalog::vendorsOf($type, $v['cat']), fn ($x) => $x['slug'] !== $v['slug']));
        usort($similar, fn ($a, $b) => [(int) ($a['area'] !== $v['area']), abs($a['price'] - $v['price'])] <=> [(int) ($b['area'] !== $v['area']), abs($b['price'] - $v['price'])]);

        $sub = $v['cat'] === 'venue'
            ? ($v['cateringBebas'] ? 'Sewa venue, catering bebas' : 'Sewa venue, belum termasuk catering')
            : ($cat['unit'] === 'pax' ? 'per pax, menu standar' : 'harga dasar, belum transport');

        $share = self::share(P::vendorPath($v, $type), $v['name'], implode(', ', array_filter([$v['hood'], P::capText($v, $type), 'mulai '.P::priceFrom($v)])));

        return self::base($v['name'], 'detail', $share + [
            'type' => $type,
            'saveKey' => P::savedKey('v', $type, $v['slug']),
            'crumb' => P::crumb([['Beranda', '/'], [Catalog::type($type)['name'], '/'.$type], [$cat['name'], '/'.$type.'/'.$v['cat']], [$area['name'], P::listingPath($type, $v['cat'], $v['area'])], [$v['name']]]),
            'name' => $v['name'],
            'photos' => $v['photos'],
            'photoLabel' => 'foto utama '.mb_strtolower($cat['name']),
            'tags' => $tags,
            'writeup' => Copy::writeup($v, $type),
            'included' => Copy::included($v, $type),
            'mapLabel' => 'peta · '.$v['hood'].', '.$area['name'],
            'faqs' => Copy::faqs($v, $type),
            'social' => ['ig' => $v['ig'], 'igHref' => 'https://instagram.com/'.ltrim($v['ig'], '@'), 'web' => $v['web'], 'webHref' => 'https://'.$v['web']],
            'box' => [
                'label' => 'Kisaran harga',
                'price' => P::priceRange($v),
                'sub' => $sub,
                'wa' => P::goHref($v['ref'], $type),
                'ref' => $v['ref'],
                'waLabel' => 'Hubungi via WhatsApp',
                'message' => P::waMessage($v['name'], $type, $v['ref']),
                'foot' => null,
            ],
            'mcta' => ['price' => P::priceFrom($v), 'waLabel' => 'WhatsApp'],
            'bundlesTitle' => 'Bundle yang memakai '.mb_strtolower($cat['name']).' ini',
            'bundles' => array_map(fn ($b) => P::bundleCard($b), array_slice($bundles, 0, 2)),
            'similarTitle' => $cat['name'].' lain yang mirip',
            'similar' => array_map(fn ($x) => P::vendorCard($x, $type), array_slice($similar, 0, 4)),
        ]);
    }

    /**
     * Tautan untuk dibagikan ke teman: URL kanonik bersih tanpa kode referral.
     * Kode referral hanya ikut di tombol hubungi vendor/EO. Ikut membentuk meta
     * Open Graph supaya pratinjau tautan di WhatsApp rapi.
     *
     * @return array{share: array, og: array}
     */
    public static function share(string $path, string $title, string $summary): array
    {
        $url = url($path);
        $text = $title.' — '.$summary.'. Lihat di EventHub:';

        return [
            'share' => ['url' => $url, 'title' => $title, 'text' => $text, 'wa' => 'https://wa.me/?text='.rawurlencode($text.' '.$url)],
            'og' => ['title' => $title.' · EventHub', 'description' => $text, 'url' => $url],
        ];
    }

    /* ---------------- bundle ---------------- */

    public static function bundle(array $b): array
    {
        $eo = Catalog::vendor($b['eo'] ?? '');
        $members = [];
        foreach ($b['members'] as [$mcat, $slug]) {
            $v = $slug ? Catalog::vendor($slug) : null;
            if (! $v) {
                continue;
            }
            $catName = Catalog::category($mcat)['name'];
            $members[] = [
                'cat' => $catName,
                'name' => $v['name'],
                'href' => P::vendorPath($v, $b['type']),
                'meta' => implode(' · ', array_filter([$v['hood'], P::capText($v, $b['type'])])),
                'more' => 'Lihat '.mb_strtolower($catName).' →',
            ];
        }
        $catList = implode(', ', array_map(fn ($m) => mb_strtolower(Catalog::category($m[0])['name']), $b['members']));

        $share = self::share(P::bundlePath($b), $b['title'], implode(', ', array_filter([
            implode(' + ', array_map(fn ($m) => Catalog::category($m[0])['name'], $b['members'])),
            Catalog::area($b['area'])['name'],
            P::bundlePrice($b),
            $eo ? 'oleh '.$eo['name'] : null,
        ])));

        return self::base($b['title'], 'bundle', $share + [
            'type' => $b['type'],
            'saveKey' => P::savedKey('b', $b['type'], $b['slug']),
            'crumb' => P::crumb([['Beranda', '/'], [Catalog::type($b['type'])['name'], '/'.$b['type']], ['Bundle', '/'.$b['type'].'/bundle'], [$b['title']]]),
            'name' => $b['title'],
            'promo' => ! empty($b['promoted']),
            'eo' => $eo ? ['name' => $eo['name'], 'href' => P::vendorPath($eo, $b['type'])] : null,
            'lead' => 'Satu kontak untuk '.count($b['members']).' vendor. EO yang mengoordinasi '.$catList.' sampai hari-H.',
            'members' => $members,
            'included' => $b['included'],
            'excluded' => $b['excluded'],
            'box' => [
                'label' => 'Kisaran harga paket',
                'price' => P::bundlePrice($b),
                'sub' => $b['perHead'] ? 'minimum '.$b['minGuests'].' peserta' : 'untuk '.$b['guests'].' pax',
                'wa' => P::goHref($b['ref'], $b['type']),
                'ref' => $b['ref'],
                'waLabel' => 'Hubungi EO via WhatsApp',
                'message' => null,
                'foot' => 'Harga dari '.($eo['name'] ?? 'EO').', berlaku per '.$b['validity'].'.',
            ],
            'mcta' => ['price' => $b['perHead'] ? $b['priceMin'].' rb/org' : $b['priceMin'].' jt', 'waLabel' => 'Hubungi EO'],
        ]);
    }

    public static function bundles(?string $type): array
    {
        $types = $type ? [$type] : ['wedding', 'corporate'];
        $sections = [];
        foreach ($types as $t) {
            $list = array_values(array_filter(Catalog::bundles(), fn ($b) => $b['type'] === $t));
            usort($list, fn ($a, $b) => (int) ! empty($b['promoted']) <=> (int) ! empty($a['promoted']));
            $sections[] = [
                'title' => Catalog::type($t)['name'],
                'sub' => $t === 'corporate' ? '— dihargai per peserta' : '',
                'cards' => array_map(fn ($b) => P::bundleCard($b, ! empty($b['promoted'])), $list),
            ];
        }

        return self::base('Bundle', 'bundles', [
            'crumb' => P::crumb($type ? [['Beranda', '/'], [Catalog::type($type)['name'], '/'.$type], ['Bundle']] : [['Beranda', '/'], ['Bundle']]),
            'h1' => 'Paket bundle'.($type ? ' '.mb_strtolower(Catalog::type($type)['name']) : ''),
            'sections' => $sections,
            'bandHref' => '/kasih-tau-kami'.($type ? '?jenis='.$type : ''),
        ]);
    }

    /* ---------------- koleksi ---------------- */

    public static function collectionPicks(array $c): array
    {
        return array_slice($c['picks'], 0, 7);
    }

    public static function collections(): array
    {
        $sections = [];
        foreach (['wedding', 'corporate'] as $t) {
            $cards = [];
            foreach (Catalog::collections() as $c) {
                if ($c['type'] !== $t) {
                    continue;
                }
                $cards[] = [
                    'href' => '/koleksi/'.$c['slug'],
                    'title' => $c['title'],
                    'sponsor' => $c['sponsor'] ?? null,
                    'meta' => count(self::collectionPicks($c)).' pilihan · '.implode(' · ', $c['locks']),
                ];
            }
            $sections[] = ['title' => Catalog::type($t)['name'], 'cards' => $cards];
        }

        return self::base('Koleksi', 'collections', [
            'crumb' => P::crumb([['Beranda', '/'], ['Koleksi']]),
            'sections' => $sections,
        ]);
    }

    public static function collection(array $c): array
    {
        $items = [];
        foreach (self::collectionPicks($c) as $i => $slug) {
            $v = Catalog::vendor($slug);
            $items[] = [
                'rank' => $i + 1,
                'key' => P::savedKey('v', $c['type'], $v['slug']),
                'name' => $v['name'],
                'href' => P::vendorPath($v, $c['type']),
                'meta' => implode(' · ', array_filter([$v['hood'].', '.Catalog::area($v['area'])['short'], P::capText($v, $c['type']), 'mulai '.P::priceFrom($v)])),
                'blurb' => Copy::highlight($v, $c['type']),
                'wa' => P::goHref($v['ref'], $c['type']),
                'ref' => $v['ref'],
            ];
        }
        $title = count($items).' '.mb_strtolower(mb_substr($c['title'], 0, 1)).mb_substr($c['title'], 1);

        return self::base($c['short'], 'collection', [
            'crumb' => P::crumb([['Beranda', '/'], ['Koleksi', '/koleksi'], [$c['short']]]),
            'sponsor' => $c['sponsor'] ?? null,
            'h1' => $title,
            'intro' => $c['intro'],
            'locks' => $c['locks'],
            'items' => $items,
            'outHref' => P::listingPath($c['type'], $c['cat'], $c['area']),
            'outLabel' => 'Lihat semua '.mb_strtolower(Catalog::category($c['cat'])['h1'][$c['type']]).' di '.($c['area'] ? Catalog::area($c['area'])['name'] : 'Jabodetabek').' →',
        ]);
    }

    /* ---------------- tersimpan ---------------- */

    public static function saved(): array
    {
        return self::base('Tersimpan', 'saved', [
            'empty' => [
                ['label' => 'Venue wedding', 'href' => '/wedding/venue', 'primary' => true],
                ['label' => 'Venue corporate', 'href' => '/corporate/venue', 'primary' => false],
                ['label' => 'Catering', 'href' => '/wedding/catering', 'primary' => false],
                ['label' => 'Bundle', 'href' => '/bundle', 'primary' => false],
            ],
        ]);
    }

    /** Data kolom tabel perbandingan untuk kunci tersimpan. */
    public static function savedItems(array $keys): array
    {
        $out = [];
        foreach (array_slice($keys, 0, 30) as $key) {
            $p = explode(':', (string) $key);
            if (count($p) !== 3 || ! Catalog::type($p[1])) {
                continue;
            }
            [$kind, $type, $slug] = $p;
            if ($kind === 'v' && ($v = Catalog::vendor($slug)) && in_array($type, $v['types'], true)) {
                $out[] = [
                    'key' => $key, 'name' => $v['name'], 'href' => P::vendorPath($v, $type),
                    'kind' => Catalog::type($type)['name'].' · '.Catalog::category($v['cat'])['name'],
                    'area' => $v['hood'].', '.Catalog::area($v['area'])['short'],
                    'capacity' => P::capText($v, $type) ?: '—',
                    'price' => P::priceFrom($v),
                    'wa' => P::goHref($v['ref'], $type), 'ref' => $v['ref'], 'waLabel' => 'WhatsApp',
                ];
            } elseif ($kind === 'b' && ($b = Catalog::bundle($slug)) && $b['type'] === $type) {
                $out[] = [
                    'key' => $key, 'name' => $b['title'], 'href' => P::bundlePath($b),
                    'kind' => Catalog::type($type)['name'].' · Bundle',
                    'area' => Catalog::area($b['area'])['name'],
                    'capacity' => $b['guests'].' '.P::guestWord($type),
                    'price' => P::bundlePrice($b),
                    'wa' => P::goHref($b['ref'], $type), 'ref' => $b['ref'], 'waLabel' => 'Hubungi EO',
                ];
            }
        }

        return $out;
    }

    /* ---------------- form ---------------- */

    public static function formOptions(): array
    {
        return [
            'jenis' => [['value' => 'wedding', 'label' => 'Wedding'], ['value' => 'corporate', 'label' => 'Corporate'], ['value' => 'lainnya', 'label' => 'Lainnya']],
            'areas' => array_merge(array_values(array_map(fn ($a) => ['value' => $a['slug'], 'label' => $a['name']], Catalog::areas())), [['value' => 'lainnya', 'label' => 'Di luar Jabodetabek']]),
        ];
    }

    public static function form(array $query): array
    {
        $pre = ['jenis' => '', 'area' => '', 'kategori' => '', 'tamu' => '', 'budget' => '', 'tanggal' => '', 'wa' => '', 'cari' => ''];
        foreach (['jenis', 'area', 'kategori', 'tamu', 'budget'] as $k) {
            $pre[$k] = is_string($query[$k] ?? null) ? $query[$k] : '';
        }
        if ($pre['kategori'] && ($c = Catalog::category($pre['kategori']))) {
            $pre['cari'] = ($c['h1'][$pre['jenis']] ?? $c['name']).'.';
        }

        return self::base('Kasih tau kami', 'form', ['prefill' => $pre, 'options' => self::formOptions()]);
    }

    public static function success(?array $sent): array
    {
        return self::base('Terkirim', 'form', ['sent' => $sent]);
    }

    public static function notFound(): array
    {
        return self::base('Tidak ditemukan', 'notfound', ['cats' => self::catRow(null), 'bandHref' => '/kasih-tau-kami']);
    }

    /* ---------------- bersama ---------------- */

    public static function footer(): array
    {
        return [
            'areas' => array_map(fn ($a) => ['label' => $a['name'], 'href' => '/wedding/venue/'.$a['slug']], array_slice(array_values(Catalog::areas()), 0, 6)),
        ];
    }
}
