<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu foto di ulasan (config media.review_sizes). Menghapus record = menghapus file. */
class ReviewPhoto extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::deleted(fn (ReviewPhoto $photo) => ImageStore::disk()->delete($photo->files()));
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function file(string $size): string
    {
        return $this->path.'-'.$size.'.webp';
    }

    /** @return list<string> */
    public function files(): array
    {
        return array_map(fn ($s) => $this->file($s), array_keys(config('media.review_sizes')));
    }

    public function url(string $size): ?string
    {
        return ImageStore::url($this->file($size));
    }
}
