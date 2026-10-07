<?php

use App\Catalog\VendorText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rincian per kategori (specs) dan semua teks halaman vendor jadi data yang bisa diedit di admin.
 * Teks yang sekarang tampil disalin sekali ke setiap vendor. null = belum pernah diisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->json('specs')->nullable()->after('photos');
            $t->text('price_note')->nullable()->after('writeup');
            $t->json('included')->nullable()->after('price_note');
            $t->json('faqs')->nullable()->after('included');
            $t->json('occasion_notes')->nullable()->after('faqs');
        });

        VendorText::fillAll();
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->dropColumn(['specs', 'price_note', 'included', 'faqs', 'occasion_notes']);
        });
    }
};
