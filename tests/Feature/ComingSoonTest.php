<?php

namespace Tests\Feature;

use App\Filament\Pages\ComingSoonSettings;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

/** Mode Segera hadir: pengunjung melihat layar pengumuman, admin tetap melihat situs. */
class ComingSoonTest extends TestCase
{
    private function turnOn(array $extra = []): void
    {
        $this->actingAs(User::factory()->create());
        Livewire::test(ComingSoonSettings::class)
            ->fillForm($extra + ['enabled' => true, 'title' => 'Sebentar lagi!', 'text' => "Kami sedang bersiap.\nSampai jumpa.", 'launch' => 'Desember 2026'])
            ->call('save')->assertHasNoFormErrors()->assertNotified();
        auth()->logout();
    }

    public function test_off_by_default_and_site_is_open(): void
    {
        $this->get('/')->assertOk()->assertDontSee('soon-copy', false);
        $this->get('/wedding')->assertOk();
    }

    public function test_visitors_see_the_screen_on_every_page_when_on(): void
    {
        Setting::put('site.contact', json_encode(['wa' => '+6282242404797', 'email' => 'kamalan.event@gmail.com', 'ig' => '', 'address' => '']));
        $this->turnOn();

        foreach (['/', '/wedding', '/wedding/venue/ballroom-kebayoran', '/tersimpan', '/halaman-tidak-ada'] as $url) {
            $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex')
                ->assertSee('Sebentar lagi!')->assertSee('Kami sedang bersiap.<br>', false)->assertSee('Desember 2026')
                ->assertDontSee('class="nav"', false);
        }
        $this->get('/')->assertSee('https://wa.me/6282242404797', false)->assertDontSee('soon-preview', false);
        $this->get('/admin/login')->assertOk();   // admin tetap bisa masuk
    }

    public function test_admins_see_the_site_with_a_reminder_and_can_preview(): void
    {
        $this->turnOn(['show_contact' => false]);
        $this->actingAs(User::factory()->create());
        $this->get('/wedding')->assertOk()->assertSee('Mode <b>Segera hadir</b> aktif', false)->assertSee('class="nav"', false);
        $this->get('/?segera-hadir=1')->assertOk()->assertSee('Sebentar lagi!')->assertSee('soon-preview', false)->assertDontSee('wa.me', false);

        // Pengunjung yang sudah punya akun (bukan admin) tetap melihat layarnya.
        $this->actingAs(User::factory()->visitor()->create());
        $this->get('/wedding')->assertSee('Sebentar lagi!');
    }

    public function test_switching_off_reopens_the_site(): void
    {
        $this->turnOn();
        $this->actingAs(User::factory()->create());
        Livewire::test(ComingSoonSettings::class)->assertFormSet(['enabled' => true, 'title' => 'Sebentar lagi!'])
            ->fillForm(['enabled' => false])->call('save')->assertHasNoFormErrors();
        auth()->logout();
        $this->get('/wedding')->assertOk()->assertDontSee('Sebentar lagi!');
    }
}
