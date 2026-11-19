<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Foto di ulasan pengunjung (maks. 5 per ulasan), dicek bersama ulasannya. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('review_id')->constrained()->cascadeOnDelete();
            $t->string('path');                       // tanpa akhiran ukuran: {path}-thumb.webp, {path}-full.webp
            $t->unsignedInteger('width')->default(0);
            $t->unsignedInteger('height')->default(0);
            $t->unsignedTinyInteger('sort')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_photos');
    }
};
