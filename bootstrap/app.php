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
        // Mode Segera hadir (admin → Konten → Segera hadir).
        $middleware->web(append: [App\Http\Middleware\ComingSoonGate::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Kiriman terlalu besar (mis. foto ulasan yang tidak sempat dikecilkan browser): kembali dengan pesan.
        $exceptions->render(function (Illuminate\Http\Exceptions\PostTooLargeException $e, Illuminate\Http\Request $request) {
            return back()->withErrors(['photos' => 'Fotonya terlalu besar. Coba kirim lebih sedikit foto sekaligus.']);
        });
        // 404 memakai tata letak situs.
        $exceptions->render(function (Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Illuminate\Http\Request $request) {
            if ($request->expectsJson() || $request->is('livewire/*')) {
                return null;
            }

            // Mode Segera hadir: alamat yang tidak ada juga menampilkan layar Segera hadir.
            return App\Http\Middleware\ComingSoonGate::screenFor($request)
                ?? response()->view('pages.not-found', ['p' => App\Catalog\Pages::notFound()], 404);
        });
    })->create();
