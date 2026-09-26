<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Satu titik render untuk kedua frontend. FRONTEND=blade merender
 * resources/views/pages/*, FRONTEND=react merender Inertia page dengan props yang sama.
 */
final class Page
{
    public static function frontend(): string
    {
        return config('app.frontend') === 'react' ? 'react' : 'blade';
    }

    public static function render(string $component, array $props, int $status = 200): Response
    {
        if (self::frontend() === 'react') {
            // Halaman 404 tanpa rute tidak melewati middleware web, jadi atur di sini juga.
            \Inertia\Inertia::setRootView('react');
            \Inertia\Inertia::share(\App\Http\Middleware\HandleInertiaRequests::siteProps(request()));

            return \Inertia\Inertia::render($component, $props)->toResponse(request())->setStatusCode($status);
        }

        return response()->view('pages.'.Str::kebab($component), ['p' => $props], $status);
    }
}
