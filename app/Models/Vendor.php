<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vendor extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'promoted' => 'boolean',
            'rooftop' => 'boolean',
            'catering_bebas' => 'boolean',
            'transit' => 'boolean',
            'av' => 'boolean',
            'pkp' => 'boolean',
            'halal' => 'boolean',
            'specs' => 'array',
            'included' => 'array',
            'faqs' => 'array',
            'occasion_notes' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Kode referral dibentuk dari kode kategori + id, sama seperti di prototipe (VNU-0104).
        static::created(function (Vendor $v) {
            if (! $v->ref) {
                $v->ref = $v->category->code.'-'.str_pad((string) $v->id, 4, '0', STR_PAD_LEFT);
                $v->saveQuietly();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function occasions(): BelongsToMany
    {
        return $this->belongsToMany(Occasion::class)->orderBy('occasions.sort');
    }
}
