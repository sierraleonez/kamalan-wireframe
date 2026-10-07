<?php

namespace App\Models;

use App\Models\Concerns\FlushesCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryOccasion extends Model
{
    use FlushesCatalog;

    protected $table = 'category_occasion';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function occasion(): BelongsTo
    {
        return $this->belongsTo(Occasion::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
