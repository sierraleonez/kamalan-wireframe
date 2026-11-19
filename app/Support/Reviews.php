<?php

namespace App\Support;

use App\Catalog\Catalog;
use App\Catalog\Present;
use App\Models\Review;
use App\Models\User;

/** Data ulasan untuk halaman vendor: ringkasan, daftar yang sudah dicek, dan ulasan milik pengunjung. */
final class Reviews
{
    /** @return array{summary: ?array, items: list<array>, occasions: list<array>, mine: ?array, viewer: list<array>, strip: list<array>, withPhotos: int} */
    public static function forVendor(array $v, ?User $user): array
    {
        $r = $v['rating'] ?? null;
        $summary = $r ? [
            'avg' => Present::ratingText($r['avg']),
            'count' => $r['count'],
            'full' => (int) round($r['avg']),
            'showAvg' => $r['count'] >= Present::RATING_MIN,
            'bars' => array_map(fn ($star) => ['star' => $star, 'n' => $r['dist'][$star] ?? 0, 'pct' => (int) round(100 * ($r['dist'][$star] ?? 0) / $r['count'])], [5, 4, 3, 2, 1]),
        ] : null;

        $rows = Review::with(['user:id,name', 'occasion:id,slug,name', 'photos'])->where('vendor_id', $v['id'])
            ->where('status', Review::APPROVED)->latest('id')->get();
        $items = $rows->map(fn (Review $x) => self::item($x))->all();

        // Semua foto dari ulasan yang tampil, berurutan, untuk strip "Foto dari pengunjung" dan penampil.
        $viewer = [];
        foreach ($items as &$it) {
            foreach ($it['photos'] as &$ph) {
                $ph['gi'] = count($viewer);
                $viewer[] = ['src' => $ph['full'], 'srcset' => '', 'width' => $ph['w'], 'height' => $ph['h'],
                    'caption' => trim($it['name'].' · ★ '.$it['rating'].($it['meta'] ? ' · '.$it['meta'] : ''))];
            }
            unset($ph);
        }
        unset($it);
        $strip = [];
        foreach ($items as $it) {
            foreach ($it['photos'] as $ph) {
                $strip[] = ['thumb' => $ph['thumb'], 'gi' => $ph['gi'], 'name' => $it['name']];
            }
        }
        $withPhotos = count(array_filter($items, fn ($it) => $it['photos']));

        $occasions = $rows->groupBy(fn ($x) => $x->occasion?->slug)->filter(fn ($g, $slug) => $slug)
            ->map(fn ($g, $slug) => ['slug' => $slug, 'name' => $g->first()->occasion->name, 'n' => $g->count()])->values()->all();

        $mine = null;
        if ($user && ($own = Review::with(['occasion:id,slug,name', 'photos'])->where(['vendor_id' => $v['id'], 'user_id' => $user->id])->first())) {
            $mine = self::item($own->setRelation('user', $user)) + ['status' => $own->status, 'reason' => $own->reject_reason];
        }

        return compact('summary', 'items', 'occasions', 'mine', 'viewer', 'strip', 'withPhotos');
    }

    private static function item(Review $x): array
    {
        $meta = array_filter([$x->occasion?->name, $x->event_month ? 'acara '.$x->event_month->locale('id')->translatedFormat('F Y') : null]);

        return [
            'id' => $x->id,
            'name' => Review::displayName($x->user?->name),
            'initial' => mb_strtoupper(mb_substr(Review::displayName($x->user?->name), 0, 1)),
            'meta' => implode(' · ', $meta),
            'occ' => $x->occasion?->slug,
            'rating' => $x->rating,
            'body' => $x->body,
            'at' => $x->created_at?->timestamp,
            'photos' => $x->photos->map(fn ($p) => ['id' => $p->id, 'thumb' => $p->url('thumb'), 'full' => $p->url('full'), 'w' => $p->width, 'h' => $p->height])->values()->all(),
        ];
    }
}
