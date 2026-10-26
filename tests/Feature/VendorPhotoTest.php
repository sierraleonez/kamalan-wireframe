<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPhoto;
use App\Support\VendorPhotos;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class VendorPhotoTest extends TestCase
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

    private function vendor(): Vendor
    {
        return Vendor::find(104);
    }

    public function test_uploads_are_stored_in_three_sizes_on_save(): void
    {
        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['new_photos' => [UploadedFile::fake()->image('a.jpg', 3000, 2000), UploadedFile::fake()->image('b.jpg', 800, 600)]])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified('2 foto ditambahkan');

        $photos = $this->vendor()->vendorPhotos;
        $this->assertCount(2, $photos);
        $disk = Storage::disk('public');
        foreach ($photos[0]->files() as $file) {
            $disk->assertExists($file);
        }
        $this->assertSame([3000, 2000], [$photos[0]->width, $photos[0]->height]);
        $this->assertSame(640, getimagesizefromstring($disk->get($photos[0]->file('card')))[0]);
        $this->assertSame(2048, getimagesizefromstring($disk->get($photos[0]->file('full')))[0]);
        $this->assertSame(800, getimagesizefromstring($disk->get($photos[1]->file('full')))[0], 'tidak diperbesar');
        $this->assertSame([], $disk->files('content/uploads'), 'unggahan sementara dihapus');
    }

    public function test_links_are_downloaded_and_bad_links_reported(): void
    {
        Http::fake([
            'img.example.test/*' => Http::response($this->png(1600, 1000), 200, ['Content-Type' => 'image/png']),
            'bad.example.test/*' => Http::response('<html>bukan gambar</html>', 200),
        ]);

        Livewire::test(EditVendor::class, ['record' => 104])
            ->fillForm(['new_photo_urls' => "https://img.example.test/a.png\nhttps://bad.example.test/x"])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertFormSet(['new_photo_urls' => null]);

        // assertNotified() menghabiskan notifikasi, jadi baca sekali dan cek keduanya.
        $notifications = new Notifications;
        $notifications->mount();
        $titles = $notifications->notifications->map(fn ($n) => $n->getTitle())->values()->all();
        $this->assertContains('1 foto ditambahkan', $titles);
        $this->assertContains('Sebagian foto tidak tersimpan', $titles);

        $this->assertSame(1, $this->vendor()->vendorPhotos()->count());
    }

    public function test_reorder_caption_and_delete_from_the_form(): void
    {
        foreach ([1, 2, 3] as $i) {
            VendorPhotos::addBytes($this->vendor(), $this->png(900, 600), 'Foto '.$i);
        }
        [$one, $two, $three] = $this->vendor()->vendorPhotos->all();

        $lw = Livewire::test(EditVendor::class, ['record' => 104]);
        $items = $lw->get('data.vendorPhotos');
        $keys = array_keys($items);
        // Urutan baru: foto 3 jadi sampul, foto 2 dihapus, keterangan foto 1 diganti.
        $items[$keys[0]]['caption'] = 'Ruang utama';
        $lw->set('data.vendorPhotos', [$keys[2] => $items[$keys[2]], $keys[0] => $items[$keys[0]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$three->id, $one->id], $this->vendor()->vendorPhotos->pluck('id')->all());
        $this->assertSame('Ruang utama', $one->fresh()->caption);
        $this->assertNull($two->fresh());
        Storage::disk('public')->assertMissing($two->file('card'));

        Catalog::flush();
        $images = Catalog::vendor('ballroom-kebayoran')['images'];
        $this->assertSame($three->url('card'), $images[0]['card'], 'yang pertama = sampul');
        $this->assertSame('Ruang utama', $images[1]['caption']);
    }

    public function test_make_cover_moves_a_photo_to_the_front(): void
    {
        foreach ([1, 2] as $i) {
            VendorPhotos::addBytes($this->vendor(), $this->png(900, 600));
        }
        [$one, $two] = $this->vendor()->vendorPhotos->all();

        $lw = Livewire::test(EditVendor::class, ['record' => 104]);
        $second = array_keys($lw->get('data.vendorPhotos'))[1];
        $lw->callAction(TestAction::make('makeCover')->schemaComponent('vendorPhotos')->arguments(['item' => $second]))
            ->call('save');

        $this->assertSame([$two->id, $one->id], $this->vendor()->vendorPhotos->pluck('id')->all());
    }

    public function test_deleting_a_vendor_removes_its_photo_files(): void
    {
        $photo = VendorPhotos::addBytes($this->vendor(), $this->png(900, 600));
        $this->vendor()->delete();

        $this->assertSame(0, VendorPhoto::count());
        foreach ($photo->files() as $file) {
            Storage::disk('public')->assertMissing($file);
        }
    }

    public function test_photo_limit_per_vendor(): void
    {
        config(['media.vendor_max_photos' => 2]);
        VendorPhotos::addBytes($this->vendor(), $this->png(100, 100));
        VendorPhotos::addBytes($this->vendor(), $this->png(100, 100));

        $this->expectExceptionMessage('Maksimal 2 foto per vendor.');
        VendorPhotos::addBytes($this->vendor(), $this->png(100, 100));
    }

    public function test_list_shows_cover_count_and_filters_vendors_without_photos(): void
    {
        $photo = VendorPhotos::addBytes($this->vendor(), $this->png(900, 600));
        $without = Vendor::where('slug', 'kebun-jagakarsa')->sole();

        $this->get('/admin/vendors')->assertOk()->assertSee('Sampul');
        Livewire::test(ListVendors::class)
            ->searchTable('Kebayoran')
            ->assertTableColumnStateSet('cover', $photo->file('card'), $this->vendor())
            ->assertTableColumnStateSet('vendor_photos_count', 1, $this->vendor());
        Livewire::test(ListVendors::class)
            ->filterTable('has_photos', false)
            ->assertCanSeeTableRecords([$without])
            ->assertCanNotSeeTableRecords([$this->vendor()]);
        Livewire::test(ListVendors::class)
            ->filterTable('has_photos', true)
            ->assertCanSeeTableRecords([$this->vendor()])
            ->assertCountTableRecords(1);
    }

    public function test_media_copy_command_copies_used_images(): void
    {
        Storage::fake('r2');
        $photo = VendorPhotos::addBytes($this->vendor(), $this->png(900, 600));

        $this->artisan('media:copy', ['from' => 'public', 'to' => 'r2'])->assertSuccessful();

        foreach ($photo->files() as $file) {
            Storage::disk('r2')->assertExists($file);
        }
    }

    public function test_public_pages_are_unchanged_in_phase_one(): void
    {
        VendorPhotos::addBytes($this->vendor(), $this->png(900, 600));
        $this->get('/wedding/venue/ballroom-kebayoran')->assertOk()->assertSee('foto utama')->assertDontSee('-card.webp');
    }
}
