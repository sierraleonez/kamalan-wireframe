<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Katalog: jenis acara, kategori, area, vendor, bundle, koleksi.
 * Kolom `sort` menyimpan urutan editorial dari catalog.json.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Jenis acara dan kategori: daftarnya tetap (teks per jenis/kategori ada di kode), isinya bisa diedit.
        Schema::create('occasions', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('guest');
            $t->string('noun');
            $t->string('phrase');
            $t->string('pane');
            $t->string('blurb');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->char('code', 3)->unique();
            $t->string('unit');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        // Kategori yang tampil di tiap jenis acara, berikut judul H1-nya.
        Schema::create('category_occasion', function (Blueprint $t) {
            $t->id();
            $t->foreignId('occasion_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained()->cascadeOnDelete();
            $t->string('h1');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->unique(['occasion_id', 'category_id']);
        });

        Schema::create('areas', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->string('short');
            $t->json('hoods');
            $t->json('near');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        // id vendor dipertahankan dari prototipe: ref = kode kategori + id (VNU-0104).
        Schema::create('vendors', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('name');
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('area_id')->constrained()->restrictOnDelete();
            $t->string('hood');
            $t->unsignedInteger('cap_min')->nullable(); // kosong untuk hiburan, dekorasi, dokumentasi, AV
            $t->unsignedInteger('cap_max')->nullable();
            $t->unsignedInteger('price');
            $t->string('setting', 10)->nullable(); // indoor | outdoor | both, hanya venue
            $t->boolean('promoted')->default(false);
            $t->unsignedSmallInteger('rank')->default(1);
            $t->boolean('rooftop')->default(false);
            $t->boolean('catering_bebas')->default(false);
            $t->boolean('transit')->default(false);
            $t->unsignedSmallInteger('parkir')->default(0);
            $t->unsignedSmallInteger('bus')->default(0);
            $t->boolean('av')->default(false);
            $t->boolean('pkp')->default(false);
            $t->boolean('halal')->default(false);
            $t->string('ref', 8)->nullable()->unique(); // diisi otomatis setelah insert
            $t->string('wa', 20);
            $t->string('ig')->default('');
            $t->string('web')->default('');
            $t->unsignedSmallInteger('photos')->default(0);
            $t->text('writeup')->nullable(); // tulisan tim; kosong = dibentuk dari data
            $t->timestamps();
        });

        // Urutan jenis acara vendor mengikuti occasions.sort; yang pertama jadi default tautan WhatsApp.
        Schema::create('occasion_vendor', function (Blueprint $t) {
            $t->id();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->foreignId('occasion_id')->constrained()->cascadeOnDelete();
            $t->unique(['vendor_id', 'occasion_id']);
        });

        Schema::create('bundles', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->foreignId('occasion_id')->constrained()->restrictOnDelete();
            $t->string('title');
            $t->foreignId('area_id')->constrained()->restrictOnDelete();
            $t->boolean('promoted')->default(false);
            $t->foreignId('eo_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $t->unsignedInteger('guests');
            $t->unsignedInteger('min_guests')->nullable();
            $t->unsignedInteger('price_min');
            $t->unsignedInteger('price_max')->nullable();
            $t->boolean('per_head')->default(false);
            $t->json('included');
            $t->json('excluded');
            $t->string('ref', 8)->nullable()->unique(); // diisi otomatis setelah insert
            $t->string('wa', 20);
            $t->string('validity');
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('bundle_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bundle_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('sort')->default(0);
        });

        Schema::create('collections', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->foreignId('occasion_id')->constrained()->restrictOnDelete();
            $t->foreignId('category_id')->constrained()->restrictOnDelete();
            $t->foreignId('area_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('title');
            $t->string('short');
            $t->json('locks');
            $t->text('intro');
            $t->string('sponsor')->nullable();
            $t->unsignedSmallInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('collection_picks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $t->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('sort')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['collection_picks', 'collections', 'bundle_members', 'bundles', 'occasion_vendor', 'vendors', 'areas', 'category_occasion', 'categories', 'occasions'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
