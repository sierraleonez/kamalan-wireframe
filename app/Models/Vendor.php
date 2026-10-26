<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        // Hapus file foto di penyimpanan; baris DB-nya ikut terhapus lewat cascade.
        static::deleting(fn (Vendor $v) => $v->vendorPhotos->each->delete());

        // Kode referral dibentuk dari kode kategori + id, sama seperti di prototipe (VNU-0104).
        static::created(function (Vendor $v) {
            if (! $v->ref) {
                $v->ref = $v->category->code.'-'.str_pad((string) $v->id, 4, '0', STR_PAD_LEFT);
                $v->saveQuietly();
            }
        });
    }

    /** Foto berurutan; yang pertama sampul. (Kolom `photos` = jumlah foto lama dari prototipe.) */
    public function vendorPhotos(): HasMany
    {
        return $this->hasMany(VendorPhoto::class)->orderBy('sort')->orderBy('id');
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
