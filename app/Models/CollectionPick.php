<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionPick extends Model
{
    use FlushesCatalog;

    public $timestamps = false;

    protected $guarded = ['id'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
