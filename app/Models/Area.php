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

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }
}
