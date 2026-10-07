<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jenis acara (wedding, corporate, …). Daftarnya tetap, lihat Catalog::TYPES. */
class Occasion extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id', 'slug'];

    /** Kategori yang tampil di jenis acara ini, berurutan, dengan judul H1. */
    public function categoryLinks(): HasMany
    {
        return $this->hasMany(CategoryOccasion::class)->orderBy('sort');
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }
}
