<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Badge Verified: empat cek tim (kunjungan, harga dari pengelola, NIB, WhatsApp resmi) + tanggal kunjungan.
 * Badge tampil kalau keempatnya dicentang dan kunjungan belum lewat 12 bulan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->boolean('verify_visit')->default(false);
            $t->boolean('verify_price')->default(false);
            $t->boolean('verify_nib')->default(false);
            $t->boolean('verify_wa')->default(false);
            $t->date('verified_on')->nullable();     // tanggal kunjungan / cek terakhir
            $t->string('verified_by', 80)->nullable();
            $t->string('nib', 40)->nullable();       // tidak tampil di situs
            $t->text('verify_note')->nullable();     // catatan internal
        });
    }

    public function down(): void
    {
        Schema::table('vendors', fn (Blueprint $t) => $t->dropColumn(['verify_visit', 'verify_price', 'verify_nib', 'verify_wa', 'verified_on', 'verified_by', 'nib', 'verify_note']));
    }
};
