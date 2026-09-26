<?php

namespace App\Http\Middleware;

use App\Catalog\Pages;
use App\Support\Page;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;

/** Aktif hanya untuk FRONTEND=react; respons Blade tidak disentuh. */
class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'react';

    public function handle(Request $request, Closure $next)
    {
        if (Page::frontend() !== 'react') {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    public function share(Request $request): array
    {
        return [...parent::share($request), ...self::siteProps($request)];
    }

    /** Props tata letak; juga dipakai saat middleware tidak jalan (404 tanpa rute). */
    public static function siteProps(Request $request): array
    {
        return [
            'path' => $request->getRequestUri(),
            'csrf' => $request->hasSession() ? $request->session()->token() : '',
            'footer' => fn () => Pages::footer(),
        ];
    }
}
