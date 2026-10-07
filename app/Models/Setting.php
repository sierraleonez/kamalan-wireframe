<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;

/** Pengaturan situs sederhana (kunci → nilai), mis. foto hero beranda. Ikut cache Catalog. */
class Setting extends Model
{
    use FlushesCatalog;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function value(string $key): ?string
    {
        return static::find($key)?->value;
    }
}
