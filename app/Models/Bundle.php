<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bundle extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'promoted' => 'boolean',
            'per_head' => 'boolean',
            'included' => 'array',
            'excluded' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Lanjutkan pola prototipe: BDL-2100, BDL-2107, … (selang 7).
        static::created(function (Bundle $b) {
            if (! $b->ref) {
                $b->ref = 'BDL-'.(2100 + 7 * ($b->id - 1));
                $b->saveQuietly();
            }
        });
    }

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function eo(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'eo_vendor_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(BundleMember::class)->orderBy('sort');
    }
}
