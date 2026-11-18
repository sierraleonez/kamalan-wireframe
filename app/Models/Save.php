<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu item di Tersimpan milik pengunjung: kunci "v:<jenis>:<slug>" / "b:<jenis>:<slug>" + catatan. */
class Save extends Model
{
    public const KEY = '/^[vb]:[a-z0-9-]{1,60}:[a-z0-9-]{1,90}$/';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
