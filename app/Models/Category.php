<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kategori vendor (venue, catering, …). Daftarnya tetap: filter dan tulisan per kategori ada di kode. */
class Category extends Model
{
    use FlushesCatalog;

    protected $guarded = ['id', 'slug', 'code'];

    public function occasionLinks(): HasMany
    {
        return $this->hasMany(CategoryOccasion::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
