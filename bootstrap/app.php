<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Caddy di depan (localhost) meneruskan HTTPS; tanpa ini sebagian URL aset jadi http://.
        $middleware->trustProxies(at: '127.0.0.1');
        // Halaman akun pengunjung yang butuh masuk mengarah ke /masuk (admin punya login sendiri).
        $middleware->redirectGuestsTo('/masuk');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 404 memakai tata letak situs.
        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null;
            }

            return response()->view('pages.not-found', ['p' => App\Catalog\Pages::notFound()], 404);
        });
    })->create();
