<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Ulasan pengunjung: satu per vendor per akun, dicek tim (pending → approved / rejected) sebelum tampil. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->foreignId('occasion_id')->nullable()->constrained()->nullOnDelete();
            $t->date('event_month')->nullable();          // tanggal 1 bulan acara
            $t->text('body');
            $t->string('status', 10)->default('pending')->index();   // pending | approved | rejected
            $t->text('reject_reason')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'vendor_id']);
            $t->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
