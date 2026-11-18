<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Builder;
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
            'verify_visit' => 'boolean',
            'verify_price' => 'boolean',
            'verify_nib' => 'boolean',
            'verify_wa' => 'boolean',
            'verified_on' => 'date',
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Badge Verified berlaku sampai (null kalau cek belum lengkap). */
    public function verifiedUntil(): ?\Illuminate\Support\Carbon
    {
        $all = $this->verify_visit && $this->verify_price && $this->verify_nib && $this->verify_wa;

        return $all && $this->verified_on ? $this->verified_on->copy()->addMonths(\App\Catalog\Present::VERIFIED_MONTHS) : null;
    }

    /** Vendor dengan keempat cek lengkap (masih berlaku atau tidak). */
    public function scopeFullyChecked(Builder $q): Builder
    {
        return $q->where(['verify_visit' => true, 'verify_price' => true, 'verify_nib' => true, 'verify_wa' => true])->whereNotNull('verified_on');
    }
}
