<?php

namespace App\Http\Controllers;

use App\Catalog\Catalog;
use App\Catalog\Present as P;
use App\Models\Occasion;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Support\ImageStore;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/** Tulis, ubah, dan hapus ulasan sendiri (harus masuk). Setiap simpan kembali ke antrean cek tim. */
class ReviewController extends Controller
{
    private function vendor(string $slug): array
    {
        $v = Catalog::vendor($slug);
        abort_unless($v && $v['types'], 404);

        return $v;
    }

    /** Halaman vendor untuk kembali: jenis acara dari ?jenis kalau dilayani, selain itu yang pertama. */
    private function back(array $v, ?string $type): string
    {
        return P::vendorPath($v, in_array($type, $v['types'], true) ? $type : $v['types'][0]).'#ulasan';
    }

    public function form(Request $request, string $slug): Response
    {
        $v = $this->vendor($slug);
        $own = Review::where(['vendor_id' => $v['id'], 'user_id' => $request->user()->id])->first();
        $months = [];
        for ($m = now()->startOfMonth(), $i = 0; $i < 24; $i++, $m = $m->copy()->subMonth()) {
            $months[$m->format('Y-m')] = $m->locale('id')->translatedFormat('F Y');
        }

        return response()->view('pages.review-form', ['p' => [
            'title' => 'Ulasan untuk '.$v['name'],
            'page' => 'account',
            'vendor' => $v['name'],
            'slug' => $v['slug'],
            'back' => $this->back($v, $request->query('jenis')),
            'jenis' => $request->query('jenis'),
            'occasions' => array_map(fn ($t) => ['slug' => $t, 'name' => Catalog::type($t)['name']], $v['types']),
            'months' => $months,
            'own' => $own,
            'ownPhotos' => $own ? $own->photos->map(fn (ReviewPhoto $ph) => ['id' => $ph->id, 'thumb' => $ph->url('thumb')])->values()->all() : [],
            'maxPhotos' => config('media.review_max_photos'),
            'name' => Review::displayName($request->user()->name),
        ]]);
    }

    public function save(Request $request, string $slug): RedirectResponse
    {
        $v = $this->vendor($slug);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'occasion' => ['nullable', 'string', 'in:'.implode(',', $v['types'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'body' => ['required', 'string', 'min:30', 'max:2000', function ($attr, $value, $fail) {
                if (preg_match('/(https?:\/\/|www\.|\.(com|id|net|co)\b)/i', $value) || preg_match('/\d{9,}/', preg_replace('/[\s.\-()+]/', '', $value))) {
                    $fail('Tanpa nomor HP atau link, ya.');
                }
            }],
            'used' => ['accepted'],
            'photos' => ['array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
            'remove' => ['array'],
            'remove.*' => ['integer'],
        ], [
            'photos.*.image' => 'Salah satu file bukan foto.',
            'photos.*.mimes' => 'Foto harus JPG, PNG, atau WebP.',
            'photos.*.max' => 'Foto maksimal 15 MB.',
            'rating.required' => 'Pilih jumlah bintang.',
            'body.required' => 'Ceritakan pengalamanmu.',
            'body.min' => 'Ceritakan sedikit lebih banyak (minimal 30 karakter).',
            'used.accepted' => 'Centang kalau kamu benar-benar memakai vendor ini.',
        ]);
        $month = ! empty($data['month']) ? Carbon::createFromFormat('Y-m-d', $data['month'].'-01') : null;
        if ($month && $month->isFuture()) {
            $month = null;
        }

        $own = Review::where(['vendor_id' => $v['id'], 'user_id' => $request->user()->id])->first();
        $keep = $own ? $own->photos->reject(fn ($p) => in_array($p->id, array_map('intval', $data['remove'] ?? []), true)) : collect();
        $new = $request->file('photos', []);
        if ($keep->count() + count($new) > config('media.review_max_photos')) {
            throw ValidationException::withMessages(['photos' => 'Maksimal '.config('media.review_max_photos').' foto per ulasan.']);
        }

        $review = Review::updateOrCreate(['vendor_id' => $v['id'], 'user_id' => $request->user()->id], [
            'rating' => (int) $data['rating'],
            'occasion_id' => ! empty($data['occasion']) ? Occasion::where('slug', $data['occasion'])->value('id') : null,
            'event_month' => $month,
            'body' => trim($data['body']),
            'status' => Review::PENDING,
            'reject_reason' => null,
            'reviewed_at' => null,
        ]);

        // Foto: yang dicentang hapus dibuang, yang baru dikecilkan jadi WebP (metadata/GPS ikut hilang).
        if ($own) {
            $own->photos->diff($keep)->each->delete();
        }
        $sort = (int) $keep->max('sort');
        foreach ($new as $file) {
            try {
                $path = ImageStore::DIR.'/reviews/'.$review->id.'/'.Str::lower(Str::random(12));
                $size = ImageStore::storeVariants($file->get(), $path, config('media.review_sizes'));
                $review->photos()->create(['path' => $path, 'width' => $size['width'], 'height' => $size['height'], 'sort' => ++$sort]);
            } catch (RuntimeException $e) {
                return redirect('/ulasan/'.$v['slug'])->withErrors(['photos' => $e->getMessage()]);
            }
        }

        return redirect($this->back($v, $data['occasion'] ?? $request->input('jenis')))->with('review_sent', true);
    }

    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $v = $this->vendor($slug);
        Review::where(['vendor_id' => $v['id'], 'user_id' => $request->user()->id])->first()?->delete();

        return redirect($this->back($v, $request->input('jenis')))->with('review_deleted', true);
    }
}
