<?php

namespace App\Models\Concerns;

use App\Catalog\Catalog;

/** Setiap perubahan data katalog membuang cache Catalog; halaman berikutnya membangunnya ulang. */
trait FlushesCatalog
{
    protected static function bootFlushesCatalog(): void
    {
        static::saved(fn () => Catalog::flush());
        static::deleted(fn () => Catalog::flush());
    }
}
