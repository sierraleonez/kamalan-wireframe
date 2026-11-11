<?php

namespace Tests\Feature;

use App\Filament\Pages\HomeContent;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Filament\Resources\Occasions\Pages\ManageOccasions;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Occasion;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Foto beranda: hero di Konten beranda, sampul di Jenis acara, foto kotak di Kategori. */
class HomeContentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
    }

    private function png(int $w, int $h): string
    {
        $im = imagecreatetruecolor($w, $h);
        ob_start();
        imagepng($im);

        return ob_get_clean();
    }

    private function newOccasion(array $extra = []): array
    {
        return $extra + [
            'name' => 'Lamaran', 'slug' => 'lamaran', 'guest' => 'tamu', 'noun' => 'lamaran', 'phrase' => 'acara lamaran',
            'pane' => 'Untuk lamaran', 'blurb' => 'Seserahan, makan bersama keluarga, dan dokumentasi.',
            'image' => UploadedFile::fake()->image('lamaran.jpg', 2000, 1300), 'image_alt' => 'Keluarga berkumpul',
            'categoryLinks' => [['category_id' => Category::where('slug', 'venue')->value('id'), 'h1' => 'Venue lamaran']],
        ];
    }

    public function test_home_content_page_has_hero_and_section_pickers(): void
    {
        $this->get('/admin/konten-beranda')->assertOk()->assertSee('Hero')->assertSee('Vendor Terbaik')->assertSee('Best Deals (paket bundle)')->assertDontSee('Kategori vendor');
        $this->get('/')->assertSee('foto acara')->assertDontSee('/storage/content/');
    }

    public function test_uploaded_hero_is_resized_to_webp_and_shown(): void
    {
        Livewire::test(HomeContent::class)
            ->fillForm(['hero.image' => UploadedFile::fake()->image('hero.jpg', 3000, 2000), 'hero.image_alt' => 'Resepsi di taman'])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = Setting::value('home.hero_image');
        $this->assertMatchesRegularExpression('#^content/beranda-hero-[0-9a-f]{10}\.webp$#', $path);
        $this->assertSame(1600, getimagesizefromstring(Storage::disk('public')->get($path))[0]);
        $this->assertSame([$path], Storage::disk('public')->allFiles('content'), 'upload asli dihapus');
        $this->get('/')->assertSee('/storage/'.$path)->assertSee('Resepsi di taman')->assertDontSee('foto acara');
    }

    public function test_hero_from_link_and_bad_link(): void
    {
        Http::fake([
            'img.example.test/*' => Http::response($this->png(1200, 800), 200, ['Content-Type' => 'image/png']),
            'bad.example.test/*' => Http::response('<html>bukan gambar</html>', 200),
        ]);

        Livewire::test(HomeContent::class)->fillForm(['hero.image_url' => 'https://bad.example.test/x'])->call('save')->assertNotified('Foto tidak tersimpan');
        $this->assertNull(Setting::value('home.hero_image'));

        Livewire::test(HomeContent::class)->fillForm(['hero.image_url' => 'https://img.example.test/a.png'])->call('save')
            ->assertNotified('Konten beranda disimpan')->assertFormSet(['hero.image_url' => null]);
        Storage::disk('public')->assertExists(Setting::value('home.hero_image'));
    }

    public function test_new_occasion_with_cover_goes_live(): void
    {
        $undo = Repeater::fake();
        Livewire::test(ManageOccasions::class)->callAction('create', data: $this->newOccasion())->assertHasNoActionErrors();
        $undo();

        $o = Occasion::where('slug', 'lamaran')->sole();
        $this->assertMatchesRegularExpression('#^content/acara-lamaran-[0-9a-f]{10}\.webp$#', $o->image);
        $this->assertSame(6, $o->sort); // setelah enam jenis acara awal (sort 0–5)

        $this->get('/')->assertSee('Lamaran')->assertSee('/storage/'.$o->image)->assertSee('Keluarga berkumpul');
        $this->get('/lamaran')->assertOk();

        // Vendor yang ditandai untuk jenis acara baru tampil di listing-nya.
        Vendor::find(104)->occasions()->attach($o->id);
        \App\Catalog\Catalog::flush();
        $this->get('/lamaran/venue')->assertOk()->assertSee('Venue lamaran')->assertSee('Ballroom Kebayoran');
        $this->get('/lamaran/venue/ballroom-kebayoran')->assertOk();
    }

    public function test_new_occasion_requires_a_cover_and_a_free_slug(): void
    {
        $undo = Repeater::fake();
        Livewire::test(ManageOccasions::class)
            ->callAction('create', data: $this->newOccasion(['image' => null]))
            ->assertHasActionErrors(['image' => 'required']);
        Livewire::test(ManageOccasions::class)
            ->callAction('create', data: $this->newOccasion(['slug' => 'koleksi']))
            ->assertHasActionErrors(['slug']);
        Livewire::test(ManageOccasions::class)
            ->callAction('create', data: $this->newOccasion(['slug' => 'wedding']))
            ->assertHasActionErrors(['slug']);
        $undo();

        $this->assertFalse(Occasion::where('slug', 'lamaran')->exists());
        $this->get('/lamaran')->assertNotFound();
    }

    public function test_existing_occasion_cover_is_set_in_its_form(): void
    {
        $wedding = Occasion::where('slug', 'wedding')->sole();
        Livewire::test(ManageOccasions::class)
            ->callTableAction('edit', $wedding, data: ['image' => UploadedFile::fake()->image('w.jpg', 1500, 1000)])
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($path = $wedding->fresh()->image);
        $this->get('/')->assertSee('/storage/'.$path);
    }

    public function test_occasion_in_use_by_bundles_cannot_be_deleted_but_a_new_one_can(): void
    {
        $this->assertFalse(\App\Filament\Resources\Occasions\OccasionResource::canDelete(Occasion::where('slug', 'wedding')->sole()));

        $undo = Repeater::fake();
        Livewire::test(ManageOccasions::class)->callAction('create', data: $this->newOccasion());
        $undo();
        $o = Occasion::where('slug', 'lamaran')->sole();
        Livewire::test(ManageOccasions::class)->callTableAction('delete', $o);

        $this->assertNull($o->fresh());
        $this->get('/lamaran')->assertNotFound();
    }

    public function test_category_photo_is_set_in_its_form_and_can_be_removed(): void
    {
        $catering = Category::where('slug', 'catering')->sole();
        Livewire::test(ManageCategories::class)
            ->callTableAction('edit', $catering, data: ['image' => UploadedFile::fake()->image('c.jpg', 800, 600), 'image_alt' => 'Prasmanan'])
            ->assertHasNoTableActionErrors();
        $path = $catering->fresh()->image;
        $this->assertSame(500, getimagesizefromstring(Storage::disk('public')->get($path))[0]);
        $this->get('/')->assertSee('/storage/'.$path)->assertSee('Prasmanan');
        $this->get('/wedding')->assertSee('/storage/'.$path);

        Livewire::test(ManageCategories::class)->callTableAction('edit', $catering, data: ['image' => null])->assertHasNoTableActionErrors();
        $this->assertNull($catering->fresh()->image);
        Storage::disk('public')->assertMissing($path);
    }

    /** Isi repeater seperti dari browser: satu baris per pilihan. */
    private function rows(array $ids): array
    {
        return array_combine(array_map(fn ($i) => 'r'.$i, array_keys($ids)), array_map(fn ($id) => ['id' => $id], $ids));
    }

    public function test_admin_picks_which_vendors_bundles_and_collections_show_on_home(): void
    {
        $catering = Vendor::where('slug', 'boga-mahardika')->first();      // bukan venue
        $venue = Vendor::where('slug', 'taman-cilandak')->first();
        $bundle = Bundle::orderByDesc('id')->first();
        $collection = Collection::orderByDesc('id')->first();

        Livewire::test(HomeContent::class)
            ->fillForm(['vendors' => $this->rows([$catering->id, $venue->id]), 'bundles' => $this->rows([$bundle->id]), 'collections' => $this->rows([$collection->id])])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(json_encode([$catering->id, $venue->id]), Setting::value('home.vendors'));

        $html = $this->get('/')->assertOk()->getContent();
        $vendors = substr($html, strpos($html, 'Vendor Terbaik'));
        $this->assertSame(2, substr_count(substr($vendors, 0, strpos($vendors, '</div>'."\n".'</div>') ?: strlen($vendors)), 'class="card'));
        $this->assertStringContainsString('Boga Mahardika', $vendors);
        $this->get('/')->assertSeeInOrder(['Best Deals', $bundle->title, 'Vendor Terbaik', 'Boga Mahardika', 'Taman Cilandak', 'Sudah Kami Kurasi', '/koleksi/'.$collection->slug], false);

        // Reload form shows the saved picks; a deleted vendor is skipped on the site.
        $this->assertSame([$catering->id, $venue->id], array_column(array_values(Livewire::test(HomeContent::class)->get('data.vendors')), 'id'));
        $catering->delete();
        $this->get('/')->assertDontSee('Boga Mahardika')->assertSee('Taman Cilandak');
    }

    public function test_empty_picks_fall_back_to_automatic_selection(): void
    {
        Setting::put('home.vendors', json_encode([Vendor::where('slug', 'taman-cilandak')->value('id')]));
        Livewire::test(HomeContent::class)->fillForm(['vendors' => []])->call('save')->assertHasNoFormErrors();

        $this->assertNull(Setting::value('home.vendors'));
        $this->get('/')->assertOk()->assertSee('Vendor Terbaik')->assertSee('Ballroom Kebayoran');
    }
}
