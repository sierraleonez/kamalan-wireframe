<?php

namespace App\Http\Controllers;

use App\Catalog\Catalog;
use App\Catalog\Pages;
use App\Catalog\Present;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function home(): Response
    {
        return Page::render('Home', Pages::home());
    }

    public function branch(string $type): Response
    {
        return Page::render('Branch', Pages::branch($type));
    }

    public function listing(Request $request, string $type, string $cat, ?string $area = null): Response
    {
        abort_unless(Catalog::hasCategory($type, $cat), 404);

        $shown = max(Pages::PER_PAGE, min(200, (int) $request->query('tampil', Pages::PER_PAGE)));

        return Page::render('Listing', Pages::listing($type, $cat, $area, $request->query(), $shown));
    }

    /** /{type}/{cat}/{slug}: slug area menang atas slug vendor. */
    public function areaOrVendor(Request $request, string $type, string $cat, string $slug): Response
    {
        abort_unless(Catalog::hasCategory($type, $cat), 404);

        if (Catalog::area($slug)) {
            return $this->listing($request, $type, $cat, $slug);
        }

        $v = Catalog::vendor($slug);
        abort_unless($v && $v['cat'] === $cat && in_array($type, $v['types'], true), 404);

        return Page::render('Detail', Pages::detail($type, $v));
    }

    /** Jumlah hasil untuk sheet filter mobile (dipakai frontend React). */
    public function count(Request $request, string $type, string $cat): JsonResponse
    {
        abort_unless(Catalog::hasCategory($type, $cat), 404);
        $area = Catalog::area($request->query('area')) ? $request->query('area') : null;

        return response()->json(['count' => Pages::countFor($type, $cat, $area, Pages::listingQuery($type, $cat, $request->query()))]);
    }

    public function bundles(?string $type = null): Response
    {
        return Page::render('Bundles', Pages::bundles($type));
    }

    public function bundle(string $type, string $slug): Response
    {
        $b = Catalog::bundle($slug);
        abort_unless($b && $b['type'] === $type, 404);

        return Page::render('Bundle', Pages::bundle($b));
    }

    public function collections(): Response
    {
        return Page::render('Collections', Pages::collections());
    }

    public function collection(string $slug): Response
    {
        $c = Catalog::collection($slug);
        abort_unless($c, 404);

        return Page::render('Collection', Pages::collection($c));
    }

    public function saved(): Response
    {
        return Page::render('Saved', Pages::saved());
    }

    public function savedData(Request $request): JsonResponse
    {
        $keys = array_filter(explode(',', (string) $request->query('keys', '')));

        return response()->json(['items' => Pages::savedItems($keys)]);
    }

    public function form(Request $request): Response
    {
        return Page::render('Form', Pages::form($request->query()));
    }

    public function submit(Request $request): RedirectResponse
    {
        $request->merge(['wa' => preg_replace('/[\s.\-]/', '', (string) $request->input('wa'))]);

        $data = $request->validate([
            'jenis' => ['required', 'in:wedding,corporate,lainnya'],
            'wa' => ['required', 'regex:/^(\+?62|0)8\d{7,11}$/'],
            'tanggal' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', 'string', 'max:60'],
            'tamu' => ['nullable', 'string', 'max:60'],
            'budget' => ['nullable', 'string', 'max:60'],
            'kategori' => ['nullable', 'string', 'max:40'],
            'cari' => ['nullable', 'string', 'max:2000'],
            'asal' => ['nullable', 'string', 'max:500'],
        ], [
            'jenis.required' => 'Pilih jenis acara.',
            'jenis.in' => 'Pilih jenis acara.',
            'wa.required' => 'Isi nomor WhatsApp.',
            'wa.regex' => 'Isi nomor WhatsApp yang diawali 08 atau 62, 10–13 digit.',
        ]);

        // Fase ini belum menyimpan ke database; hanya menampilkan konfirmasi.
        $due = Carbon::now()->locale('id');
        for ($n = 2; $n > 0;) {
            $due->addDay();
            if (! $due->isWeekend()) {
                $n--;
            }
        }

        $cat = Catalog::category($data['kategori'] ?? '');
        $area = Catalog::area($data['area'] ?? null);
        $summary = implode(' · ', array_filter([
            ['wedding' => 'Wedding', 'corporate' => 'Corporate'][$data['jenis']] ?? 'Lainnya',
            $cat['name'] ?? null,
            $area['name'] ?? null,
            ! empty($data['tamu']) ? $data['tamu'].' tamu' : null,
            $data['budget'] ?? null,
            $data['tanggal'] ?? null,
        ]));

        $back = null;
        $origin = parse_url((string) ($data['asal'] ?? ''), PHP_URL_PATH) ?: '';
        $seg = array_values(array_filter(explode('/', $origin)));
        if (count($seg) >= 2 && Catalog::hasCategory($seg[0], $seg[1]) && (! isset($seg[2]) || Catalog::area($seg[2]))) {
            $a = isset($seg[2]) ? Catalog::area($seg[2]) : null;
            $back = ['href' => Present::listingPath($seg[0], $seg[1], $a['slug'] ?? null), 'label' => 'Lanjut lihat '.mb_strtolower(Catalog::category($seg[1])['h1'][$seg[0]]).($a ? ' di '.$a['name'] : '')];
        } elseif (in_array($data['jenis'], ['wedding', 'corporate'], true)) {
            $back = ['href' => '/'.$data['jenis'], 'label' => 'Lanjut menjelajah '.Catalog::type($data['jenis'])['name']];
        }

        return redirect('/kasih-tau-kami/terkirim')->with('sent', [
            'wa' => $data['wa'],
            'due' => $due->translatedFormat('l, j F'),
            'summary' => $summary,
            'back' => $back,
        ]);
    }

    public function success(Request $request): Response
    {
        return Page::render('Success', Pages::success($request->session()->get('sent')));
    }

    /** Teruskan ke WhatsApp. Nanti di sini klik dicatat sebelum redirect. */
    public function go(Request $request, string $ref): RedirectResponse
    {
        $hit = Catalog::byRef($ref);
        abort_unless($hit, 404);
        [$kind, $item] = $hit;
        $type = $request->query('t');

        if ($kind === 'v') {
            $type = in_array($type, $item['types'], true) ? $type : $item['types'][0];
            $url = Present::waUrl($item['wa'], Present::waMessage($item['name'], $type, $item['ref']));
        } else {
            $url = Present::waUrl($item['wa'], Present::waMessage($item['title'], $item['type'], $item['ref'], true));
        }

        return redirect()->away($url);
    }
}
