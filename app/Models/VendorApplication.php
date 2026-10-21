<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pendaftaran vendor dari form /daftarkan-usaha. */
class VendorApplication extends Model
{
    protected $fillable = ['business_name', 'contact_name', 'wa', 'category', 'area', 'handled_at'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }
}
