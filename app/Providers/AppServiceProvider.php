<?php

namespace App\Providers;

use Illuminate\Foundation\Console\ServeCommand;
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
        //
    }
}
