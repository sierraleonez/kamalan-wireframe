<?php

use App\Catalog\Catalog;
use App\Http\Controllers\AccountController as A;
use App\Http\Controllers\SiteController as S;
use App\Http\Controllers\VendorSignupController;
use Illuminate\Support\Facades\Route;

$type = Catalog::typePattern(); // slug apa pun selain kata yang dipakai halaman lain (tanpa query database)

$slug = '[a-z0-9-]+';

Route::get('/', [S::class, 'home']);
Route::get('/bundle', [S::class, 'bundles']);
Route::get('/koleksi', [S::class, 'collections']);
Route::get('/koleksi/{slug}', [S::class, 'collection'])->where('slug', $slug);
Route::get('/tersimpan', [S::class, 'saved']);
Route::get('/tersimpan/data', [S::class, 'savedData']);
Route::get('/kasih-tau-kami', [S::class, 'form']);
Route::post('/kasih-tau-kami', [S::class, 'submit']);
Route::get('/kasih-tau-kami/terkirim', [S::class, 'success']);
Route::get('/daftarkan-usaha', [VendorSignupController::class, 'form']);
Route::post('/daftarkan-usaha', [VendorSignupController::class, 'submit']);
Route::get('/daftarkan-usaha/terkirim', [VendorSignupController::class, 'done']);
// Akun pengunjung (ACCOUNTS=true): masuk tanpa kata sandi, ♡ dan catatan Tersimpan per akun.
Route::middleware(App\Http\Middleware\AccountsEnabled::class)->group(function () {
    Route::get('/masuk', [A::class, 'show']);
    Route::post('/masuk', [A::class, 'send'])->middleware('throttle:30,1');
    Route::get('/masuk/kode', [A::class, 'code']);
    Route::post('/masuk/kode', [A::class, 'verify'])->middleware('throttle:30,1');
    Route::post('/masuk/kirim-ulang', [A::class, 'resend'])->middleware('throttle:10,1');
    Route::get('/auth/google', [A::class, 'google']);
    Route::get('/auth/google/callback', [A::class, 'googleCallback']);
    Route::middleware('auth')->group(function () {
        Route::get('/masuk/nama', [A::class, 'name']);
        Route::post('/masuk/nama', [A::class, 'saveName']);
        Route::post('/keluar', [A::class, 'logout']);
        Route::post('/akun/simpan', [A::class, 'toggle']);
        Route::post('/akun/catatan', [A::class, 'note']);
        Route::post('/akun/impor', [A::class, 'import']);
        Route::post('/akun/hapus-semua', [A::class, 'clear']);
        Route::get('/ulasan/{slug}', [App\Http\Controllers\ReviewController::class, 'form'])->where('slug', '[a-z0-9-]+');
        Route::post('/ulasan/{slug}', [App\Http\Controllers\ReviewController::class, 'save'])->where('slug', '[a-z0-9-]+')->middleware('throttle:10,1');
        Route::post('/ulasan/{slug}/hapus', [App\Http\Controllers\ReviewController::class, 'destroy'])->where('slug', '[a-z0-9-]+');
    });
});
Route::get('/privasi', [S::class, 'privacy']);
Route::get('/go/{ref}', [S::class, 'go'])->where('ref', '[A-Z]{3}-[0-9]{4}');

Route::get('/{type}', [S::class, 'branch'])->where('type', $type);
Route::get('/{type}/bundle', [S::class, 'bundles'])->where('type', $type);
Route::get('/{type}/bundle/{slug}', [S::class, 'bundle'])->where(['type' => $type, 'slug' => $slug]);
Route::get('/{type}/{cat}', [S::class, 'listing'])->where(['type' => $type, 'cat' => $slug]);
Route::get('/{type}/{cat}/{slug}', [S::class, 'areaOrVendor'])->where(['type' => $type, 'cat' => $slug, 'slug' => $slug]);
