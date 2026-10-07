<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Kiriman form "Kasih tau kami". Isian disimpan apa adanya seperti yang diketik pengunjung. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('jenis', 40);
            $t->string('wa', 20);
            $t->string('tanggal', 100)->nullable();
            $t->string('area', 60)->nullable();
            $t->string('tamu', 60)->nullable();
            $t->string('budget', 60)->nullable();
            $t->string('kategori', 40)->nullable();
            $t->text('cari')->nullable();
            $t->string('asal', 500)->nullable();
            $t->timestamp('handled_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
