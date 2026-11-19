<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun tim (is_admin, masuk /admin dengan kata sandi) dan pengunjung (masuk tanpa kata sandi lewat Google
 * atau kode email; hanya untuk Tersimpan dan ulasan). Admin baru: make:filament-user lalu user:admin.
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'joined_via',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Hapus akun → ulasan (dan file fotonya) ikut terhapus lewat model.
        static::deleting(fn (User $u) => $u->reviews->each->delete());
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function saves(): HasMany
    {
        return $this->hasMany(Save::class);
    }

    /** Huruf pertama nama (atau email) untuk lingkaran di header. */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name ?: $this->email, 0, 1));
    }
}
