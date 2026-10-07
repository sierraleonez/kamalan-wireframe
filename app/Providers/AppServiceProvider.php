<?php

namespace App\Providers;

use App\Catalog\Catalog;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // kamalan.service menambah deploy/php/uploads.ini lewat PHP_INI_SCAN_DIR; teruskan ke `artisan serve`.
        ServeCommand::$passthroughVariables[] = 'PHP_INI_SCAN_DIR';
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rute {type} menerima slug apa pun (Catalog::typePattern); yang tidak ada di database → 404.
        // Di sini, bukan di routes/web.php, supaya tetap jalan dengan route:cache.
        Route::bind('type', fn (string $value) => Catalog::type($value) ? $value : abort(404));
    }
}
