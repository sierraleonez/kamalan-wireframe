<?php

namespace App\Http\Controllers;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\Save;
use App\Models\User;
use App\Support\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Akun pengunjung tanpa kata sandi: Google, atau email + kode 6 digit (sekaligus membuktikan emailnya).
 * Daftar dan masuk satu alur; akun baru lalu ke halaman nama. Juga API kecil untuk ♡ dan catatan Tersimpan.
 */
class AccountController extends Controller
{
    private function page(string $view, string $title, array $props = []): Response
    {
        return response()->view('pages.'.$view, ['p' => ['title' => $title, 'page' => 'account'] + $props]);
    }

    /* ---------- masuk dengan email ---------- */

    public function show(Request $request): Response|RedirectResponse
    {
        if ($request->hasAny(['next', 'save']) || ! session()->has('account.next')) {
            Account::remember($request->query('next'), $request->query('save'));
        }
        if ($user = $request->user()) {
            return redirect(Account::login($user));
        }

        return $this->page('account-login', 'Masuk', ['google' => Account::googleReady(), 'email' => Account::emailReady()]);
    }

    public function send(Request $request): RedirectResponse
    {
        abort_unless(Account::emailReady(), 404);
        $email = mb_strtolower(trim((string) $request->validate(['email' => ['required', 'email:rfc', 'max:190']], ['email' => 'Tulis alamat email yang benar.'])['email']));

        session(['account.email' => $email]);
        // Kode yang baru saja dikirim dan belum dipakai masih berlaku: langsung ke halaman kode.
        $recent = LoginCode::where('email', $email)->whereNull('used_at')->where('expires_at', '>', now())
            ->where('created_at', '>', now()->subSeconds(config('accounts.resend_seconds')))->exists();
        if ($recent) {
            return redirect('/masuk/kode')->with('resent', 'Kode sudah kami kirim barusan. Cek emailmu.');
        }
        if ($error = $this->sendCode($request, $email)) {
            return back()->withInput()->withErrors(['email' => $error]);
        }

        return redirect('/masuk/kode');
    }

    public function resend(Request $request): RedirectResponse
    {
        abort_unless(Account::emailReady(), 404);
        $email = session('account.email');
        if (! $email) {
            return redirect('/masuk');
        }
        $error = $this->sendCode($request, $email);

        return back()->with($error ? 'resend_error' : 'resent', $error ?: 'Kode baru sudah dikirim.');
    }

    /** Buat dan kirim kode; mengembalikan pesan kalau ditolak (batas kiriman). */
    private function sendCode(Request $request, string $email): ?string
    {
        $last = LoginCode::where('email', $email)->whereNull('used_at')->latest('id')->first();
        if ($last && $last->created_at->gt(now()->subSeconds(config('accounts.resend_seconds')))) {
            return 'Kode baru bisa diminta '.config('accounts.resend_seconds').' detik setelah yang terakhir.';
        }
        // Di belakang Cloudflare, ip() adalah server Cloudflare; alamat pengunjung ada di CF-Connecting-IP.
        $ip = $request->header('CF-Connecting-IP') ?: $request->ip();
        foreach (['login-code:'.sha1($email) => 5, 'login-code-ip:'.$ip => 20] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return 'Terlalu banyak permintaan kode. Coba lagi dalam satu jam.';
            }
        }
        foreach (['login-code:'.sha1($email), 'login-code-ip:'.$ip] as $key) {
            RateLimiter::hit($key, 3600);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        LoginCode::where('email', $email)->whereNull('used_at')->update(['used_at' => now()]);   // yang lama tidak berlaku
        LoginCode::create(['email' => $email, 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(config('accounts.code_minutes'))]);
        Mail::to($email)->send(new LoginCodeMail($code, config('accounts.code_minutes')));

        return null;
    }

    public function code(): Response|RedirectResponse
    {
        if (! session('account.email')) {
            return redirect('/masuk');
        }

        return $this->page('account-code', 'Masukkan kode', ['email' => session('account.email'), 'wait' => config('accounts.resend_seconds')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $email = session('account.email');
        if (! $email) {
            return redirect('/masuk');
        }
        $input = preg_replace('/\D/', '', (string) $request->input('code'));
        $code = LoginCode::where('email', $email)->whereNull('used_at')->latest('id')->first();

        if (! $code || $code->expires_at->isPast()) {
            return back()->withErrors(['code' => 'Kodenya sudah tidak berlaku. Minta kode baru di bawah.']);
        }
        if ($code->attempts >= config('accounts.code_attempts')) {
            $code->update(['used_at' => now()]);

            return back()->withErrors(['code' => 'Terlalu banyak percobaan. Minta kode baru di bawah.']);
        }
        $code->increment('attempts');
        if (strlen($input) !== 6 || ! Hash::check($input, $code->code_hash)) {
            return back()->withErrors(['code' => 'Kodenya belum cocok. Cek lagi email dari Kamalan.']);
        }
        $code->update(['used_at' => now()]);
        session()->forget('account.email');

        $user = User::firstOrCreate(['email' => $email], ['joined_via' => 'email']);
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect(Account::login($user));
    }

    /* ---------- Google ---------- */

    public function google(Request $request): RedirectResponse
    {
        abort_unless(Account::googleReady(), 404);
        Account::remember($request->query('next'), $request->query('save'));

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(): RedirectResponse
    {
        abort_unless(Account::googleReady(), 404);
        try {
            $g = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect('/masuk')->withErrors(['google' => 'Masuk dengan Google gagal. Coba lagi, atau pakai email.']);
        }
        $email = mb_strtolower((string) $g->getEmail());
        if ($email === '') {
            return redirect('/masuk')->withErrors(['google' => 'Akun Google itu tidak punya email.']);
        }

        $user = User::where('google_id', $g->getId())->first() ?? User::where('email', $email)->first();
        if ($user) {
            $user->forceFill(['google_id' => $g->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        } else {
            $user = User::create(['email' => $email, 'name' => mb_substr((string) $g->getName(), 0, 60) ?: null, 'google_id' => $g->getId(), 'joined_via' => 'google', 'email_verified_at' => now()]);
        }

        return redirect(Account::login($user));
    }

    /* ---------- nama (akun baru) dan keluar ---------- */

    public function name(Request $request): Response
    {
        return $this->page('account-name', 'Nama kamu', ['email' => $request->user()->email, 'name' => $request->user()->name, 'next' => Account::safeNext($request->query('next'))]);
    }

    public function saveName(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:60']], ['name.required' => 'Tulis namamu dulu.']);
        $request->user()->update(['name' => trim($data['name'])]);

        return redirect(Account::safeNext($request->input('next')));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /* ---------- ♡ dan catatan (JSON) ---------- */

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'regex:'.Save::KEY], 'on' => ['required', 'boolean']]);
        $data['on'] ? Account::add($request->user(), $data['key']) : $request->user()->saves()->where('key', $data['key'])->delete();

        return response()->json(Account::state($request->user()));
    }

    public function note(Request $request): JsonResponse
    {
        $data = $request->validate(['key' => ['required', 'string', 'regex:'.Save::KEY], 'note' => ['nullable', 'string', 'max:2000']]);
        $request->user()->saves()->where('key', $data['key'])->update(['note' => $data['note']]);

        return response()->json(['ok' => true]);
    }

    /** Simpanan lama di browser (sebelum ada akun) pindah ke akun, sekali. */
    public function import(Request $request): JsonResponse
    {
        $data = $request->validate(['keys' => ['array', 'max:100'], 'keys.*' => ['string'], 'notes' => ['array']]);
        foreach ($data['keys'] ?? [] as $key) {
            $note = $data['notes'][$key] ?? null;
            Account::add($request->user(), $key, is_string($note) ? $note : null);
        }

        return response()->json(Account::state($request->user()));
    }

    public function clear(Request $request): JsonResponse
    {
        $request->user()->saves()->delete();

        return response()->json(Account::state($request->user()));
    }
}
