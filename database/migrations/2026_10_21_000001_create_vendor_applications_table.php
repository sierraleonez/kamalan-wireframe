<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pendaftaran dari vendor lewat /daftarkan-usaha. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_applications', function (Blueprint $t) {
            $t->id();
            $t->string('business_name', 120);
            $t->string('contact_name', 80);
            $t->string('wa', 20);
            $t->string('category', 40);   // slug kategori, atau 'lainnya'
            $t->string('area', 60);       // slug area, atau 'lainnya'
            $t->timestamp('handled_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_applications');
    }
};
