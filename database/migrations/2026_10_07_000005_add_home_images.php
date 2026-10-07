<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Foto beranda: hero (settings), kartu jenis acara, dan kartu kategori. Path relatif ke disk public. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        foreach (['occasions', 'categories'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('image')->nullable();
                $t->string('image_alt')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        foreach (['occasions', 'categories'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn(['image', 'image_alt']));
        }
    }
};
