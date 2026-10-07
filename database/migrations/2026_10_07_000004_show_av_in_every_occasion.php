<?php

use App\Catalog\CatalogDefaults;
use App\Catalog\VendorText;
use Illuminate\Database\Migrations\Migration;

/** AV / produksi tidak lagi hanya untuk acara kantor. Vendor AV dapat panel awal untuk jenis acara barunya. */
return new class extends Migration
{
    public function up(): void
    {
        CatalogDefaults::avEverywhere();
        VendorText::fillAll();
    }

    public function down(): void
    {
        // Data saja; dihapus lewat admin (Jenis acara → Kategori yang tampil) kalau perlu.
    }
};
