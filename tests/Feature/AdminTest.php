<?php

namespace Tests\Feature;

use App\Filament\Resources\Bundles\Pages\EditBundle;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Lead;
use App\Models\Occasion;
use App\Models\User;
use App\Models\Vendor;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_guest_is_sent_to_login(): void
    {
        auth()->logout();
        $this->get('/admin/vendors')->assertRedirect('/admin/login');
    }

    public static function pages(): array
    {
        return [['/admin'], ['/admin/vendors'], ['/admin/vendors/create'], ['/admin/bundles'], ['/admin/bundles/create'],
            ['/admin/collections'], ['/admin/collections/create'], ['/admin/areas'], ['/admin/occasions'], ['/admin/categories'], ['/admin/leads']];
    }

    #[DataProvider('pages')]
    public function test_admin_page_renders(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public function test_edit_pages_render(): void
    {
        $this->get('/admin/vendors/104/edit')->assertOk()->assertSee('Ballroom Kebayoran');
        $this->get('/admin/bundles/'.Bundle::first()->id.'/edit')->assertOk();
        $this->get('/admin/collections/'.Collection::first()->id.'/edit')->assertOk();
    }

    public function test_price_edit_in_admin_shows_on_listing(): void
    {
        $this->get('/wedding/venue/jakarta-selatan')->assertSee('Rp 45 jt');

        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['price' => 47])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(47, Vendor::find(104)->price);
        $this->get('/wedding/venue/jakarta-selatan')->assertSee('Rp 47 jt');
    }

    public function test_occasion_change_in_admin_updates_vendor_urls(): void
    {
        $this->get('/corporate/venue/ballroom-kebayoran')->assertOk();

        $keep = Occasion::whereIn('slug', ['wedding', 'perayaan'])->pluck('id')->all();
        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['occasions' => $keep])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/corporate/venue/ballroom-kebayoran')->assertNotFound();
        $this->get('/perayaan/venue/ballroom-kebayoran')->assertOk();
    }

    public function test_vendor_created_in_admin_is_live(): void
    {
        $venue = Category::where('slug', 'venue')->sole();

        Livewire::test(CreateVendor::class)
            ->fillForm([
                'name' => 'Pendopo Cipete', 'slug' => 'pendopo-cipete', 'category_id' => $venue->id, 'area_id' => 1,
                'hood' => 'Cilandak', 'occasions' => [Occasion::where('slug', 'wedding')->value('id')],
                'cap_min' => 100, 'cap_max' => 250, 'price' => 30, 'setting' => 'both', 'wa' => '628120001111',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $v = Vendor::where('slug', 'pendopo-cipete')->sole();
        $this->assertSame('VNU-'.str_pad((string) $v->id, 4, '0', STR_PAD_LEFT), $v->ref);
        $this->get('/wedding/venue/pendopo-cipete')->assertOk()->assertSee('Pendopo Cipete');
        $this->get('/go/'.$v->ref.'?t=wedding&p=DETAIL')->assertRedirectContains('wa.me/628120001111');
    }

    public function test_vendor_form_rejects_bad_whatsapp_and_capacity(): void
    {
        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['wa' => '08123', 'cap_min' => 500, 'cap_max' => 100])
            ->call('save')
            ->assertHasFormErrors(['wa', 'cap_max']);
    }

    public function test_bundle_member_change_shows_on_bundle_page(): void
    {
        $bundle = Bundle::where('slug', 'paket-intimate-wedding-150-pax')->sole();
        $this->get('/wedding/bundle/'.$bundle->slug)->assertSee('Janur Rasa');

        Livewire::test(EditBundle::class, ['record' => $bundle->id])
            ->fillForm(['title' => 'Paket Intimate Wedding 160 pax'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/wedding/bundle/'.$bundle->slug)->assertSee('Paket Intimate Wedding 160 pax');
    }

    public function test_collection_intro_edit_shows_on_collection_page(): void
    {
        $c = Collection::where('slug', 'rooftop-jaksel-dibawah-50jt')->sole();

        Livewire::test(EditCollection::class, ['record' => $c->id])
            ->fillForm(['intro' => 'Pengantar baru dari tim kurasi.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/koleksi/'.$c->slug)->assertSee('Pengantar baru dari tim kurasi.');
    }

    public function test_lead_can_be_marked_handled(): void
    {
        $lead = Lead::create(['jenis' => 'wedding', 'wa' => '081234567890']);

        Livewire::test(ManageLeads::class)
            ->assertCanSeeTableRecords([$lead])
            ->callTableAction('toggleHandled', $lead);

        $this->assertNotNull($lead->fresh()->handled_at);
    }
}
