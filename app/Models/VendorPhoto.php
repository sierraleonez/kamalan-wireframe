<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu foto vendor dalam beberapa ukuran (config media.vendor_sizes). Menghapus record = menghapus file. */
class VendorPhoto extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleted(function (VendorPhoto $photo) {
            ImageStore::disk()->delete($photo->files());
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** Path file untuk satu ukuran: card, gallery, atau full. */
    public function file(string $size): string
    {
        return $this->path.'-'.$size.'.webp';
    }

    /** @return list<string> */
    public function files(): array
    {
        return array_map(fn ($size) => $this->file($size), array_keys(config('media.vendor_sizes')));
    }

    public function url(string $size = 'card'): string
    {
        return ImageStore::url($this->file($size));
    }
}
