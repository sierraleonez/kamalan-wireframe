<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Akun pengunjung (masuk tanpa kata sandi: Google atau kode 6 digit lewat email) dan simpanan per akun.
 * Akun tim yang sudah ada jadi admin; hanya admin yang bisa membuka /admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('name')->nullable()->change();
            $t->string('password')->nullable()->change();
            $t->boolean('is_admin')->default(false)->after('password');
            $t->string('google_id')->nullable()->unique()->after('is_admin');
            $t->string('joined_via', 10)->nullable()->after('google_id');   // 'google' | 'email' | null (tim)
        });
        DB::table('users')->update(['is_admin' => true]);

        Schema::create('login_codes', function (Blueprint $t) {
            $t->id();
            $t->string('email')->index();
            $t->string('code_hash');
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('expires_at');
            $t->timestamp('used_at')->nullable();
            $t->timestamps();
        });

        Schema::create('saves', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('key', 160);              // "v:<jenis>:<slug>" atau "b:<jenis>:<slug>"
            $t->text('note')->nullable();        // catatan pribadi di Tersimpan
            $t->timestamps();
            $t->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saves');
        Schema::dropIfExists('login_codes');
        Schema::table('users', function (Blueprint $t) {
            $t->dropUnique(['google_id']);
            $t->dropColumn(['is_admin', 'google_id', 'joined_via']);
        });
    }
};
