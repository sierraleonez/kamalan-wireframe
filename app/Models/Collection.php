<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Koleksi kurasi (/koleksi/{slug}). */
class Collection extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['locks' => 'array'];
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function picks(): HasMany
    {
        return $this->hasMany(CollectionPick::class)->orderBy('sort');
    }
}
