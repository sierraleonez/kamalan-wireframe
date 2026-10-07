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
use Filament\Forms\Components\Repeater;
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
        $this->assertNotNull($v->faqs);
        $this->assertSame(6, $v->specs['duration_hours']);
        $this->get('/wedding/venue/pendopo-cipete')->assertOk()->assertSee('Pendopo Cipete')->assertSee('Boleh bawa catering sendiri?');
        $this->get('/go/'.$v->ref.'?t=wedding&p=DETAIL')->assertRedirectContains('wa.me/628120001111');
    }

    public function test_form_shows_only_the_fields_of_the_chosen_category(): void
    {
        $id = fn (string $slug) => Category::where('slug', $slug)->value('id');

        Livewire::test(CreateVendor::class)
            ->fillForm(['category_id' => $id('venue')])
            ->assertFormFieldIsVisible('specs.duration_hours')->assertFormFieldIsVisible('parkir')->assertFormFieldIsVisible('setting')
            ->assertFormFieldIsHidden('halal')->assertFormFieldIsHidden('specs.duration_min')
            ->fillForm(['category_id' => $id('catering')])
            ->assertFormFieldIsVisible('halal')->assertFormFieldIsVisible('specs.waiter_ratio')->assertFormFieldIsVisible('cap_min')
            ->assertFormFieldIsHidden('parkir')->assertFormFieldIsHidden('rooftop')->assertFormFieldIsHidden('specs.duration_hours')
            ->fillForm(['category_id' => $id('eo')])
            ->assertFormFieldIsVisible('specs.crew_max')->assertFormFieldIsVisible('bus')->assertFormFieldIsHidden('transit')
            ->fillForm(['category_id' => $id('hiburan')])
            ->assertFormFieldIsVisible('specs.duration_min')->assertFormFieldIsHidden('cap_min')->assertFormFieldIsHidden('av')
            ->assertFormFieldIsVisible('pkp')->assertFormFieldIsVisible('specs.dp_percent')
            ->fillForm(['category_id' => $id('dokumentasi')])
            ->assertFormFieldIsVisible('specs.delivery_days')->assertFormFieldIsHidden('specs.setup_hours');
    }

    public function test_venue_duration_updates_facts_and_rewrite_updates_text(): void
    {
        $this->get('/wedding/venue/ballroom-kebayoran')->assertSee('Sewa 6 jam');

        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['specs.duration_hours' => 8])
            ->call('save')
            ->assertHasNoFormErrors();

        // Fakta langsung ikut; teks yang sudah disalin tetap sampai ditulis ulang.
        $this->get('/wedding/venue/ballroom-kebayoran')->assertSee('8 jam')->assertSee('Sewa 6 jam');

        Livewire::test(EditVendor::class, ['record' => 104])->callAction('regenerate');

        $this->get('/wedding/venue/ballroom-kebayoran')->assertSee('Sewa 8 jam')->assertDontSee('Sewa 6 jam');
    }

    public function test_page_text_edits_show_on_the_site(): void
    {
        $undo = Repeater::fake();

        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm([
                'price_note' => 'Sewa 8 jam termasuk kursi.',
                'faqs' => [['q' => 'Boleh bawa kembang api?', 'a' => 'Tidak boleh di dalam gedung.']],
                'included' => [['label' => 'Kursi tiffany', 'note' => '300 unit']],
                'occasion_notes' => [['occasion' => 'wedding', 'title' => 'Untuk resepsi', 'text' => "Paragraf satu.\n\nParagraf dua."]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        $undo();

        $this->get('/wedding/venue/ballroom-kebayoran')
            ->assertSee('Sewa 8 jam termasuk kursi.')
            ->assertSee('Boleh bawa kembang api?')->assertSee('Tidak boleh di dalam gedung.')->assertDontSee('Boleh bawa catering sendiri?')
            ->assertSee('Kursi tiffany')
            ->assertSee('Untuk resepsi')->assertSee('Paragraf dua.');
    }

    public function test_added_occasion_gets_a_starting_panel(): void
    {
        $v = Vendor::find(104);
        $this->assertNotContains('ulang-tahun', array_column($v->occasion_notes, 'occasion'));

        $ids = $v->occasions->pluck('id')->push(Occasion::where('slug', 'ulang-tahun')->value('id'))->all();
        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['occasions' => $ids])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertContains('ulang-tahun', array_column(Vendor::find(104)->occasion_notes, 'occasion'));
        $this->get('/ulang-tahun/venue/ballroom-kebayoran')->assertOk()->assertSee('Untuk ulang tahun');
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
