<?php

namespace App\Support;

use App\Models\Save;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Akun pengunjung di situs: apakah aktif, cara masuk yang tersedia, simpanan per akun,
 * dan "simpan setelah masuk" (vendor yang diketuk tamu, diingat di sesi sampai ia masuk).
 */
final class Account
{
    public static function enabled(): bool
    {
        return (bool) config('accounts.enabled');
    }

    public static function googleReady(): bool
    {
        return (bool) config('accounts.google');
    }

    public static function emailReady(): bool
    {
        return (bool) config('accounts.email');
    }

    /** Alamat aman untuk kembali setelah masuk: hanya path di situs ini. */
    public static function safeNext(?string $next): string
    {
        return is_string($next) && preg_match('#^/(?!/)[^\s\\\\]*$#', $next) ? $next : '/';
    }

    /** Ingat tujuan kembali dan vendor yang mau disimpan, dari ?next= dan ?save=. */
    public static function remember(?string $next, ?string $save): void
    {
        session(['account.next' => self::safeNext($next)]);
        if (is_string($save) && preg_match(Save::KEY, $save)) {
            session(['account.save' => $save]);
        }
    }

    /** Masukkan pengguna, simpan vendor yang tertunda, dan kembalikan tujuan berikutnya. */
    public static function login(User $user): string
    {
        Auth::login($user, true);
        session()->regenerate();
        if ($key = session()->pull('account.save')) {
            self::add($user, $key);
        }
        $next = session()->pull('account.next', '/');

        return $user->name ? $next : '/masuk/nama?next='.rawurlencode($next);
    }

    public static function add(User $user, string $key, ?string $note = null): void
    {
        if (! preg_match(Save::KEY, $key) || ($user->saves()->count() >= config('accounts.max_saves') && ! $user->saves()->where('key', $key)->exists())) {
            return;
        }
        $save = $user->saves()->firstOrCreate(['key' => $key]);
        if ($note !== null && blank($save->note)) {
            $save->update(['note' => mb_substr($note, 0, 2000)]);
        }
    }

    /** Data untuk halaman: kunci tersimpan dan catatannya. */
    public static function state(?User $user): array
    {
        if (! $user) {
            return ['in' => false];
        }
        $saves = $user->saves()->orderBy('id')->get(['key', 'note']);

        return [
            'in' => true,
            'name' => $user->name,
            'keys' => $saves->pluck('key')->all(),
            'notes' => (object) $saves->filter(fn ($s) => filled($s->note))->pluck('note', 'key')->all(),
        ];
    }
}
