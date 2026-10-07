<?php

namespace Tests\Feature;

use App\Filament\Pages\HomeContent;
use App\Models\Category;
use App\Models\Occasion;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

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

    public function test_page_renders(): void
    {
        $this->get('/admin/konten-beranda')->assertOk()->assertSee('Hero')->assertSee('Wedding')->assertSee('Venue');
    }

    public function test_home_shows_placeholders_without_photos(): void
    {
        $this->get('/')->assertSee('foto acara')->assertDontSee('/storage/content/');
    }

    public function test_uploaded_hero_is_resized_to_webp_and_shown(): void
    {
        Livewire::test(HomeContent::class)
            ->fillForm(['hero.image' => UploadedFile::fake()->image('hero.jpg', 3000, 2000), 'hero.alt' => 'Resepsi di taman'])
            ->call('save')
            ->assertHasNoFormErrors();

        $path = Setting::value('home.hero_image');
        $this->assertMatchesRegularExpression('#^content/beranda-hero-[0-9a-f]{10}\.webp$#', $path);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(1600, getimagesizefromstring(Storage::disk('public')->get($path))[0]);
        $this->assertSame([$path], Storage::disk('public')->allFiles('content'), 'upload asli dihapus');

        $this->get('/')->assertSee('/storage/'.$path)->assertSee('Resepsi di taman')->assertDontSee('foto acara');
    }

    public function test_image_from_link_is_downloaded_for_an_occasion(): void
    {
        Http::fake(['img.example.test/*' => Http::response($this->png(1200, 800), 200, ['Content-Type' => 'image/png'])]);

        Livewire::test(HomeContent::class)
            ->fillForm(['occ.wedding.url' => 'https://img.example.test/wedding.png'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertFormSet(['occ.wedding.url' => null]);

        $path = Occasion::where('slug', 'wedding')->value('image');
        Storage::disk('public')->assertExists($path);
        $this->assertSame(1000, getimagesizefromstring(Storage::disk('public')->get($path))[0]);
        $this->get('/')->assertSee('/storage/'.$path);
    }

    public function test_bad_link_shows_an_error_and_saves_nothing_for_that_slot(): void
    {
        Http::fake(['bad.example.test/*' => Http::response('<html>bukan gambar</html>', 200)]);

        Livewire::test(HomeContent::class)
            ->fillForm(['cat.venue.url' => 'https://bad.example.test/x'])
            ->call('save')
            ->assertHasErrors(['data.cat.venue.url']);

        $this->assertNull(Category::where('slug', 'venue')->value('image'));
    }

    public function test_category_photo_shows_and_can_be_removed(): void
    {
        Livewire::test(HomeContent::class)
            ->fillForm(['cat.catering.image' => UploadedFile::fake()->image('c.jpg', 800, 600)])
            ->call('save')
            ->assertHasNoFormErrors();
        $path = Category::where('slug', 'catering')->value('image');
        $this->get('/')->assertSee('/storage/'.$path);
        $this->get('/wedding')->assertSee('/storage/'.$path);

        Livewire::test(HomeContent::class)
            ->fillForm(['cat.catering.image' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(Category::where('slug', 'catering')->value('image'));
        Storage::disk('public')->assertMissing($path);
    }
}
