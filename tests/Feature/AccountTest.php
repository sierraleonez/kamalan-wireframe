<?php

namespace Tests\Feature;

use App\Mail\LoginCodeMail;
use App\Models\LoginCode;
use App\Models\User;
use App\Support\Account;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

/** Akun pengunjung: masuk tanpa kata sandi (kode email / Google), ♡ hanya setelah masuk, simpanan per akun. */
class AccountTest extends TestCase
{
    private const KEY = 'v:wedding:ballroom-kebayoran';

    protected function setUp(): void
    {
        parent::setUp();
        config(['accounts.enabled' => true, 'accounts.email' => true, 'accounts.google' => true,
            'services.google.client_id' => 'test-id', 'services.google.client_secret' => 'test-secret']);
        Mail::fake();
    }

    /** Minta kode untuk email ini dan kembalikan kode yang "terkirim". */
    private function requestCode(string $email = 'rina@example.com'): string
    {
        $this->post('/masuk', ['email' => $email])->assertRedirect('/masuk/kode');
        $code = null;
        Mail::assertSent(LoginCodeMail::class, function (LoginCodeMail $m) use ($email, &$code) {
            $code = $m->code;

            return $m->hasTo($email);
        });

        return $code;
    }

    public function test_switched_off_the_site_works_as_before(): void
    {
        config(['accounts.enabled' => false]);
        $this->get('/masuk')->assertNotFound();
        $this->actingAs(User::factory()->visitor()->create())->postJson('/akun/simpan', ['key' => self::KEY, 'on' => true])->assertNotFound();
        $this->post('/keluar');
        auth()->logout();
        $this->get('/wedding')->assertOk()->assertDontSee('account-state')->assertDontSee('href="/masuk', false);
        $this->get('/tersimpan')->assertSee('Tersimpan di browser ini');
    }

    public function test_guest_sees_masuk_and_the_sign_in_box_and_tersimpan_asks_to_sign_in(): void
    {
        $this->get('/wedding/venue')->assertOk()
            ->assertSee('href="/masuk?next=%2Fwedding%2Fvenue"', false)
            ->assertSee('Masuk untuk menyimpan')
            ->assertSee('"in":false', false);
        $this->get('/tersimpan')->assertOk()->assertSee('Masuk dulu, ya')->assertDontSee('x-data="savedPage"', false);
    }

    public function test_new_visitor_signs_up_with_an_email_code_names_themselves_and_the_vendor_is_saved(): void
    {
        $this->get('/masuk?next=%2Fwedding%2Fvenue&save='.self::KEY)->assertOk()->assertSee('Masuk atau daftar')->assertSee('Lanjut dengan Google');
        $code = $this->requestCode();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, LoginCode::first()->code_hash, 'kode disimpan sebagai hash');

        $this->get('/masuk/kode')->assertOk()->assertSee('rina@example.com');
        $this->post('/masuk/kode', ['code' => '000000' === $code ? '111111' : '000000'])->assertSessionHasErrors('code');
        $this->post('/masuk/kode', ['code' => $code])->assertRedirect('/masuk/nama?next=%2Fwedding%2Fvenue');

        $user = User::where('email', 'rina@example.com')->first();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->is_admin);
        $this->assertSame('email', $user->joined_via);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame([self::KEY], $user->saves()->pluck('key')->all(), 'vendor yang diketuk tersimpan');

        $this->get('/masuk/nama?next=%2Fwedding%2Fvenue')->assertOk()->assertSee('Siapa namamu?');
        $this->post('/masuk/nama', ['name' => ''])->assertSessionHasErrors('name');
        $this->post('/masuk/nama', ['name' => 'Rina Putri', 'next' => '/wedding/venue'])->assertRedirect('/wedding/venue');
        $this->get('/wedding/venue')->assertSee('Rina Putri')->assertSee('"keys":["'.self::KEY.'"]', false)->assertDontSee('Masuk untuk menyimpan');
    }

    public function test_returning_visitor_goes_straight_back_and_codes_are_one_use(): void
    {
        User::factory()->visitor()->create(['email' => 'rina@example.com', 'name' => 'Rina']);
        $this->get('/masuk?next=%2Fkoleksi');
        $code = $this->requestCode();
        $this->post('/masuk/kode', ['code' => $code])->assertRedirect('/koleksi');

        $this->post('/keluar')->assertRedirect('/');
        $this->assertGuest();
        $this->withSession(['account.email' => 'rina@example.com'])->post('/masuk/kode', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_codes_expire_and_lock_after_five_wrong_tries(): void
    {
        $code = $this->requestCode();
        $this->travel(11)->minutes();
        $this->post('/masuk/kode', ['code' => $code])->assertSessionHasErrors(['code' => 'Kodenya sudah tidak berlaku. Minta kode baru di bawah.']);
        $this->travelBack();

        $this->travel(1)->minutes();
        Mail::fake();
        $code = $this->requestCode();
        $wrong = $code === '123456' ? '654321' : '123456';
        foreach (range(1, 5) as $i) {
            $this->post('/masuk/kode', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        $this->post('/masuk/kode', ['code' => $code])->assertSessionHasErrors(['code' => 'Terlalu banyak percobaan. Minta kode baru di bawah.']);
        $this->assertGuest();
    }

    public function test_resend_waits_45_seconds_and_requests_are_limited_per_email(): void
    {
        $this->requestCode();
        $this->post('/masuk/kirim-ulang')->assertSessionHas('resend_error');
        $this->travel(46)->seconds();
        $this->post('/masuk/kirim-ulang')->assertSessionHas('resent');
        foreach (range(1, 3) as $i) {
            $this->travel(46)->seconds();
            $this->post('/masuk/kirim-ulang')->assertSessionHas('resent');
        }
        $this->travel(46)->seconds();
        $this->post('/masuk/kirim-ulang')->assertSessionHas('resend_error', 'Terlalu banyak permintaan kode. Coba lagi dalam satu jam.');
        $this->post('/masuk', ['email' => 'bukan-email'])->assertSessionHasErrors('email');

        // Masuk lagi di perangkat lain segera setelah memakai kode: kode baru langsung dikirim.
        $this->travel(2)->hours();
        Mail::fake();
        $code = $this->requestCode('dewi@example.com');
        $this->post('/masuk/kode', ['code' => $code]);
        $this->post('/keluar');
        Mail::fake();
        $this->requestCode('dewi@example.com');
        // Minta lagi sebelum memakai kode: tidak dikirim dua kali, langsung ke halaman kode.
        Mail::fake();
        $this->post('/masuk', ['email' => 'dewi@example.com'])->assertRedirect('/masuk/kode')->assertSessionHas('resent');
        Mail::assertNothingSent();
    }

    private function fakeGoogle(string $id, string $email, string $name): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn((new GoogleUser)->map(['id' => $id, 'email' => $email, 'name' => $name]));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    }

    public function test_google_sign_in_creates_or_links_the_account_and_skips_the_name_page(): void
    {
        $this->get('/auth/google?next=%2Fwedding&save='.self::KEY)->assertRedirectContains('accounts.google.com');

        $this->fakeGoogle('g-1', 'Dewi@Gmail.com', 'Dewi Lestari');
        $this->withSession(['account.next' => '/wedding', 'account.save' => self::KEY])->get('/auth/google/callback')->assertRedirect('/wedding');
        $dewi = User::where('email', 'dewi@gmail.com')->first();
        $this->assertSame(['g-1', 'google', 'Dewi Lestari'], [$dewi->google_id, $dewi->joined_via, $dewi->name]);
        $this->assertSame(1, $dewi->saves()->count());

        // Email yang sudah terdaftar lewat kode: akun yang sama, sekarang juga bisa masuk lewat Google.
        $this->post('/keluar');
        $rina = User::factory()->visitor()->create(['email' => 'rina@gmail.com', 'name' => 'Rina']);
        $this->fakeGoogle('g-2', 'rina@gmail.com', 'Rina G');
        $this->get('/auth/google/callback')->assertRedirect('/');
        $this->assertSame('g-2', $rina->fresh()->google_id);
        $this->assertSame('Rina', $rina->fresh()->name);
        $this->assertSame(2, User::count());
    }

    public function test_save_api_toggles_notes_imports_and_clears_per_account(): void
    {
        $rina = User::factory()->visitor()->create();
        $other = User::factory()->visitor()->create();
        $other->saves()->create(['key' => 'v:wedding:taman-cilandak']);

        $this->postJson('/akun/simpan', ['key' => self::KEY, 'on' => true])->assertUnauthorized();

        $this->actingAs($rina);
        $this->postJson('/akun/simpan', ['key' => self::KEY, 'on' => true])->assertOk()->assertJson(['keys' => [self::KEY]]);
        $this->postJson('/akun/simpan', ['key' => 'bukan kunci', 'on' => true])->assertUnprocessable();
        $this->postJson('/akun/catatan', ['key' => self::KEY, 'note' => 'Survey Sabtu'])->assertOk();
        $this->postJson('/akun/impor', ['keys' => [self::KEY, 'b:wedding:paket-intimate-wedding-150-pax', 'x'], 'notes' => ['b:wedding:paket-intimate-wedding-150-pax' => 'dari browser']])
            ->assertOk()->assertJson(['keys' => [self::KEY, 'b:wedding:paket-intimate-wedding-150-pax'], 'notes' => [self::KEY => 'Survey Sabtu', 'b:wedding:paket-intimate-wedding-150-pax' => 'dari browser']]);
        $this->postJson('/akun/simpan', ['key' => self::KEY, 'on' => false])->assertJson(['keys' => ['b:wedding:paket-intimate-wedding-150-pax']]);

        $this->get('/tersimpan')->assertSee('Tersimpan di akun '.$rina->email)->assertSee('x-data="savedPage"', false);
        $this->postJson('/akun/hapus-semua')->assertJson(['keys' => []]);
        $this->assertSame(1, $other->saves()->count(), 'simpanan akun lain tidak tersentuh');
    }

    public function test_visitors_cannot_open_admin_and_admins_see_the_visitor_list(): void
    {
        $visitor = User::factory()->visitor()->create(['name' => 'Rina', 'email' => 'rina@example.com']);
        $visitor->saves()->create(['key' => self::KEY]);
        $this->actingAs($visitor)->get('/admin')->assertForbidden();

        $admin = User::factory()->create(['email' => 'tim@kamalan.test']);
        $this->actingAs($admin)->get('/admin/pengunjung')->assertOk()->assertSee('rina@example.com')->assertDontSee('tim@kamalan.test');

        $this->artisan('user:admin rina@example.com')->assertSuccessful();
        $this->assertTrue($visitor->fresh()->is_admin);
    }

    public function test_only_local_paths_are_followed_after_sign_in(): void
    {
        $this->assertSame('/wedding?x=1', Account::safeNext('/wedding?x=1'));
        foreach (['//evil.test', 'https://evil.test', '/\\evil.test', null, 'wedding'] as $bad) {
            $this->assertSame('/', Account::safeNext($bad));
        }
        $this->get('/masuk?next=//evil.test')->assertOk();
        $this->assertSame('/', session('account.next'));
    }

    public function test_privacy_page_explains_what_is_stored(): void
    {
        $this->get('/privasi')->assertOk()->assertSee('Data yang kami simpan')->assertSee('kamalan.event@gmail.com');
        $this->get('/masuk')->assertSee('href="/privasi"', false);
    }
}
