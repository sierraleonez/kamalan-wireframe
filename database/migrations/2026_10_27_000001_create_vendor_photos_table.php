<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto vendor. Setiap foto disimpan dalam beberapa ukuran WebP di penyimpanan media:
 * "{path}-card.webp", "{path}-gallery.webp", "{path}-full.webp". Urutan pertama = sampul.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->string('path');                  // tanpa akhiran ukuran, mis. content/vendors/104/k3j2h1
            $t->unsignedInteger('width');        // ukuran asli
            $t->unsignedInteger('height');
            $t->string('caption', 160)->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
            $t->index(['vendor_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_photos');
    }
};
