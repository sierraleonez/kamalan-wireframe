<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Kiriman form "Kasih tau kami". */
class Lead extends Model
{
    protected $fillable = ['jenis', 'wa', 'tanggal', 'area', 'tamu', 'budget', 'kategori', 'cari', 'asal'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }
}
