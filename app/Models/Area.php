<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['hoods' => 'array', 'near' => 'array'];
    }

    protected static function booted(): void
    {
        // Ganti slug: perbarui rujukan "area terdekat" di area lain.
        static::updated(function (Area $a) {
            if ($a->wasChanged('slug')) {
                $old = $a->getOriginal('slug');
                foreach (Area::whereKeyNot($a->id)->get() as $other) {
                    if (in_array($old, $other->near, true)) {
                        $other->near = array_map(fn ($s) => $s === $old ? $a->slug : $s, $other->near);
                        $other->save();
                    }
                }
            }
        });

        // Hapus: buang dari "area terdekat" area lain.
        static::deleted(function (Area $a) {
            foreach (Area::all() as $other) {
                if (in_array($a->slug, $other->near, true)) {
                    $other->near = array_values(array_diff($other->near, [$a->slug]));
                    $other->save();
                }
            }
        });
    }

    /** Dipakai bundle atau koleksi (vendor dicek terpisah). */
    public function hasDependents(): bool
    {
        return Bundle::where('area_id', $this->id)->exists() || Collection::where('area_id', $this->id)->exists();
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
