<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kode masuk 6 digit yang dikirim ke email; disimpan sebagai hash, sekali pakai. */
class LoginCode extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }
}
