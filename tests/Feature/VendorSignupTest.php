<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Filament\Resources\VendorApplications\Pages\ManageVendorApplications;
use App\Models\User;
use App\Models\VendorApplication;
use Livewire\Livewire;
use Tests\TestCase;

class VendorSignupTest extends TestCase
{
    private function valid(array $extra = []): array
    {
        return $extra + ['business_name' => 'Gedung Melati', 'contact_name' => 'Rina', 'wa' => '0812 3456 7890', 'category' => 'venue', 'area' => 'jakarta-selatan'];
    }

    public function test_home_shows_the_vendor_band_instead_of_the_request_band(): void
    {
        $this->get('/')->assertSee('Punya usaha vendor acara?')->assertSee('href="/daftarkan-usaha"', false)
            ->assertDontSee('Belum Menemukan yang Cocok?')->assertDontSee('Isi kebutuhan');
    }

    public function test_other_pages_keep_the_request_band(): void
    {
        $this->get('/wedding')->assertSee('Belum Menemukan yang Cocok?')->assertDontSee('Punya usaha vendor acara?');
        $this->get('/kasih-tau-kami')->assertOk();
    }

    public function test_footer_link_points_to_the_vendor_form(): void
    {
        $this->get('/wedding')->assertSee('<a href="/daftarkan-usaha" wire:navigate>Daftarkan usaha</a>', false);
    }

    public function test_form_renders_with_categories_and_areas(): void
    {
        $this->get('/daftarkan-usaha')->assertOk()->assertSee('Daftarkan usahamu di Kamalan.')
            ->assertSee('<option value="catering"', false)->assertSee('<option value="jakarta-selatan"', false);
    }

    public function test_valid_signup_is_saved_and_thanked(): void
    {
        $this->post('/daftarkan-usaha', $this->valid())->assertRedirect('/daftarkan-usaha/terkirim');
        $a = VendorApplication::sole();
        $this->assertSame(['Gedung Melati', 'Rina', '081234567890', 'venue', 'jakarta-selatan'], [$a->business_name, $a->contact_name, $a->wa, $a->category, $a->area]);

        $this->withSession(['vendor_sent' => ['name' => 'Gedung Melati', 'wa' => '081234567890']])
            ->get('/daftarkan-usaha/terkirim')->assertSee('pendaftaranmu sudah kami terima')->assertSee('Gedung Melati');
    }

    public function test_invalid_signup_saves_nothing(): void
    {
        $this->post('/daftarkan-usaha', $this->valid(['wa' => '123', 'category' => 'roket', 'business_name' => '']))
            ->assertSessionHasErrors(['wa', 'category', 'business_name']);
        $this->assertSame(0, VendorApplication::count());
    }

    public function test_slug_is_reserved_for_occasions(): void
    {
        $this->assertContains('daftarkan-usaha', Catalog::RESERVED);
    }

    public function test_admin_lists_signups_and_marks_them_handled(): void
    {
        $this->actingAs(User::factory()->create());
        $a = VendorApplication::create(['business_name' => 'Gedung Melati', 'contact_name' => 'Rina', 'wa' => '081234567890', 'category' => 'venue', 'area' => 'jakarta-selatan']);

        $this->get('/admin/vendor-applications')->assertOk()->assertSee('Gedung Melati');
        Livewire::test(ManageVendorApplications::class)->assertCanSeeTableRecords([$a])->callTableAction('toggleHandled', $a);
        $this->assertNotNull($a->fresh()->handled_at);
    }
}
