<?php

use App\Http\Controllers\SiteController as S;
use Illuminate\Support\Facades\Route;

$type = 'wedding|corporate';
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
Route::get('/go/{ref}', [S::class, 'go'])->where('ref', '[A-Z]{3}-[0-9]{4}');

Route::get('/{type}', [S::class, 'branch'])->where('type', $type);
Route::get('/{type}/bundle', [S::class, 'bundles'])->where('type', $type);
Route::get('/{type}/bundle/{slug}', [S::class, 'bundle'])->where(['type' => $type, 'slug' => $slug]);
Route::get('/{type}/{cat}/hitung', [S::class, 'count'])->where(['type' => $type, 'cat' => $slug]);
Route::get('/{type}/{cat}', [S::class, 'listing'])->where(['type' => $type, 'cat' => $slug]);
Route::get('/{type}/{cat}/{slug}', [S::class, 'areaOrVendor'])->where(['type' => $type, 'cat' => $slug, 'slug' => $slug]);
