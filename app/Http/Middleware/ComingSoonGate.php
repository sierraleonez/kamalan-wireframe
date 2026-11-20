<?php

namespace App\Http\Middleware;

use App\Catalog\Catalog;
use App\Support\ComingSoon;
use App\Support\SiteContact;
use Closure;
use Illuminate\Http\Request;

/**
 * Saat mode Segera hadir aktif: semua halaman situs menampilkan layar Segera hadir untuk pengunjung.
 * Admin yang masuk melihat situs biasa (dengan pita pengingat), dan bisa melihat layarnya lewat ?segera-hadir.
 * /admin dan Livewire selalu jalan supaya mode ini bisa dimatikan lagi.
 */
class ComingSoonGate
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin', 'admin/*', 'livewire/*', 'filament/*', 'up') || ! ComingSoon::active()) {
            return $next($request);
        }
        return self::screenFor($request) ?? $next($request);
    }

    /** Layar Segera hadir untuk permintaan ini, atau null kalau situs biasa yang tampil (mode mati / admin). */
    public static function screenFor(Request $request)
    {
        if (! ComingSoon::active() || $request->is('admin', 'admin/*', 'livewire/*', 'filament/*', 'up')) {
            return null;
        }
        $admin = (bool) $request->user()?->is_admin;
        if ($admin && ! $request->has('segera-hadir')) {
            return null;
        }

        $c = ComingSoon::config();

        return response()->view('pages.coming-soon', ['p' => [
            'title' => $c['title'],
            'page' => 'soon',
            'soon' => $c,
            'photo' => $c['show_photo'] ? Catalog::image('hero') : null,
            'contact' => $c['show_contact'] ? SiteContact::links(Catalog::data()['contact'] ?? SiteContact::fromSettings([])) : [],
            'preview' => $admin,
        ]])->header('X-Robots-Tag', 'noindex');
    }
}
