<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\User;
use App\Models\Vendor;
use Livewire\Livewire;
use Tests\TestCase;

/** Badge Verified (model B): centang setelah nama di kartu dan judul halaman vendor. */
class VerifiedBadgeTest extends TestCase
{
    private function verify(string $slug, array $extra = []): Vendor
    {
        $v = Vendor::where('slug', $slug)->first();
        $v->update($extra + ['verify_visit' => true, 'verify_price' => true, 'verify_nib' => true, 'verify_wa' => true, 'verified_on' => now()->subMonth()]);
        Catalog::flush();

        return $v;
    }

    public function test_verified_vendor_gets_a_tick_on_cards_and_its_page(): void
    {
        $this->verify('ballroom-kebayoran', ['verified_on' => '2026-10-12']);
        $this->travelTo('2026-11-19');

        $this->get('/wedding/venue')->assertOk()
            ->assertSee('<div class="nm-row"><a class="nm card-link" href="/wedding/venue/ballroom-kebayoran"', false)
            ->assertSee('aria-label="Terverifikasi Kamalan. Apa artinya?"', false)
            ->assertSee('Apa artinya Verified?');
        $this->get('/wedding/venue/ballroom-kebayoran')->assertOk()
            ->assertSee('class="display vtitle"', false)
            ->assertSee('Terverifikasi Kamalan · dicek Oktober 2026');
        // Vendor lain tanpa tanda apa pun.
        $this->get('/wedding/venue/taman-cilandak')->assertOk()->assertDontSee('Terverifikasi Kamalan ·')->assertDontSee('vtitle');
    }

    public function test_badge_needs_all_four_checks_and_a_date_and_expires_after_twelve_months(): void
    {
        $this->verify('ballroom-kebayoran', ['verify_nib' => false]);
        $this->get('/wedding/venue/ballroom-kebayoran')->assertDontSee('vtitle');

        $this->verify('ballroom-kebayoran', ['verified_on' => null]);
        $this->get('/wedding/venue/ballroom-kebayoran')->assertDontSee('vtitle');

        $this->verify('ballroom-kebayoran', ['verified_on' => now()->subMonths(12)->subDay()]);
        $this->get('/wedding/venue/ballroom-kebayoran')->assertDontSee('vtitle');

        $this->verify('ballroom-kebayoran', ['verified_on' => now()->subMonths(11)]);
        $this->get('/wedding/venue/ballroom-kebayoran')->assertSee('vtitle');
    }

    public function test_listing_filter_shows_only_verified_vendors(): void
    {
        $this->verify('ballroom-kebayoran');
        $this->get('/wedding/venue')->assertSee('Hanya terverifikasi')->assertSee('Taman Cilandak');
        $this->get('/wedding/venue?terverifikasi=1')->assertOk()->assertSee('Ballroom Kebayoran')->assertDontSee('Taman Cilandak');
    }

    public function test_admin_sets_the_checks_and_lists_vendors_due_for_a_recheck(): void
    {
        $this->actingAs(User::factory()->create());
        $v = Vendor::where('slug', 'ballroom-kebayoran')->first();

        Livewire::test(EditVendor::class, ['record' => $v->id])
            ->fillForm(['verify_visit' => true, 'verify_price' => true, 'verify_nib' => true, 'verify_wa' => true, 'verified_on' => null])
            ->call('save')->assertHasFormErrors(['verified_on' => 'required']);
        Livewire::test(EditVendor::class, ['record' => $v->id])
            ->fillForm(['verify_visit' => true, 'verify_price' => true, 'verify_nib' => true, 'verify_wa' => true, 'verified_on' => now()->subMonths(11)->subDays(20)->toDateString(), 'nib' => '9120001234567', 'verified_by' => 'Dimas'])
            ->call('save')->assertHasNoFormErrors();
        $this->assertNotNull($v->fresh()->verifiedUntil());
        $this->get('/wedding/venue/ballroom-kebayoran')->assertSee('vtitle')->assertDontSee('9120001234567');

        $fresh = $this->verify('taman-cilandak', ['verified_on' => now()->subMonths(2)]);
        Livewire::test(ListVendors::class)
            ->filterTable('verification', 'recheck')->assertCanSeeTableRecords([$v])->assertCanNotSeeTableRecords([$fresh])
            ->filterTable('verification', 'active')->assertCanSeeTableRecords([$v, $fresh]);
    }
}
