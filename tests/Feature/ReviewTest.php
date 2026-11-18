<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Mail\ReviewRejectedMail;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/** Ulasan pengunjung: hanya yang masuk, satu per vendor, dicek tim, rata-rata di kartu mulai 3 ulasan. */
class ReviewTest extends TestCase
{
    private const PAGE = '/wedding/venue/ballroom-kebayoran';

    private const BODY = 'Ruangannya luas dan pengelola cepat membalas di WhatsApp. Parkir agak terbatas.';

    protected function setUp(): void
    {
        parent::setUp();
        config(['accounts.enabled' => true, 'accounts.email' => true]);
    }

    private function vendor(): Vendor
    {
        return Vendor::where('slug', 'ballroom-kebayoran')->first();
    }

    private function review(int $rating, string $status = Review::APPROVED, ?string $name = null): Review
    {
        return Review::create(['vendor_id' => $this->vendor()->id, 'user_id' => User::factory()->visitor()->create(['name' => $name ?? fake()->name()])->id,
            'rating' => $rating, 'body' => self::BODY, 'status' => $status]);
    }

    public function test_guests_are_sent_to_sign_in_and_come_back_to_the_form(): void
    {
        $this->get(self::PAGE)->assertOk()->assertSee('Ulasan pengunjung')->assertSee('Belum ada ulasan')
            ->assertSee('href="/masuk?next=%2Fulasan%2Fballroom-kebayoran%3Fjenis%3Dwedding"', false);
        $this->get('/ulasan/ballroom-kebayoran?jenis=wedding')->assertRedirect('/masuk');
        $this->get('/masuk')->assertOk();
        $this->assertSame('/ulasan/ballroom-kebayoran?jenis=wedding', session('account.next'));
        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 5, 'body' => self::BODY, 'used' => 1])->assertRedirect('/masuk');
        $this->assertSame(0, Review::count());
    }

    public function test_visitor_writes_one_review_that_waits_for_the_team(): void
    {
        $rina = User::factory()->visitor()->create(['name' => 'Rina Putri']);
        $this->actingAs($rina)->get('/ulasan/ballroom-kebayoran?jenis=wedding')->assertOk()->assertSee('Tulis ulasan')->assertSee('Rina P.');

        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 5, 'body' => 'terlalu pendek', 'used' => 1])->assertSessionHasErrors('body');
        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 5, 'body' => self::BODY.' Hubungi 0812 3456 7890', 'used' => 1])->assertSessionHasErrors('body');
        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 5, 'body' => self::BODY.' lihat www.contoh.com', 'used' => 1])->assertSessionHasErrors('body');
        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 5, 'body' => self::BODY])->assertSessionHasErrors('used');
        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 9, 'body' => self::BODY, 'used' => 1])->assertSessionHasErrors('rating');

        $this->post('/ulasan/ballroom-kebayoran', ['rating' => 4, 'occasion' => 'wedding', 'month' => '2026-10', 'body' => self::BODY, 'used' => 1, 'jenis' => 'wedding'])
            ->assertRedirect(self::PAGE.'#ulasan');
        $r = Review::first();
        $this->assertSame([Review::PENDING, 4, '2026-10-01'], [$r->status, $r->rating, $r->event_month->toDateString()]);

        // Hanya penulis yang melihatnya, ditandai sedang dicek; pengunjung lain tidak.
        $this->get(self::PAGE)->assertSee('sedang dicek tim')->assertSee('Ubah ulasanmu')->assertDontSee('"body":"Ruangannya', false);
        auth()->logout();
        $this->get(self::PAGE)->assertDontSee('Ruangannya luas');

        // Mengubah: tetap satu ulasan, kembali ke antrean.
        $r->update(['status' => Review::APPROVED]);
        $this->actingAs($rina)->post('/ulasan/ballroom-kebayoran', ['rating' => 3, 'body' => self::BODY.' Update.', 'used' => 1]);
        $this->assertSame(1, Review::count());
        $this->assertSame([Review::PENDING, 3], [$r->fresh()->status, $r->fresh()->rating]);

        $this->post('/ulasan/ballroom-kebayoran/hapus')->assertRedirect();
        $this->assertSame(0, Review::count());
    }

    public function test_approved_reviews_show_with_name_initial_and_average_from_three(): void
    {
        $this->review(5, name: 'Dewi Lestari');
        $this->review(4);
        Catalog::flush();
        $this->get(self::PAGE)->assertSee('2 ulasan')->assertSee('Rata-rata tampil setelah ada 3 ulasan')->assertSee('Dewi L.')->assertDontSee('Lestari');
        $this->get('/wedding/venue')->assertDontSee('class="rate"', false);

        $this->review(4);
        $this->review(2, Review::PENDING);   // belum dicek: tidak dihitung
        Catalog::flush();
        $this->get('/wedding/venue')->assertSee('aria-label="Rating 4,3 dari 5, 3 ulasan"', false)->assertSee('<b>4,3</b> (3)', false);
        $this->get(self::PAGE)->assertSee('<b>4,3</b> · 3 ulasan', false)->assertSee('dari 3 ulasan');
    }

    public function test_admin_approves_and_rejects_with_a_reason_emailed_to_the_writer(): void
    {
        Mail::fake();
        $pending = $this->review(5, Review::PENDING);
        $other = $this->review(1, Review::PENDING);
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageReviews::class)->assertCanSeeTableRecords([$pending, $other])
            ->callAction(TestAction::make('approve')->table($pending))
            ->callAction(TestAction::make('reject')->table($other), ['reason' => 'Berisi promosi.']);

        $this->assertSame(Review::APPROVED, $pending->fresh()->status);
        $this->assertSame([Review::REJECTED, 'Berisi promosi.'], [$other->fresh()->status, $other->fresh()->reject_reason]);
        Mail::assertSent(ReviewRejectedMail::class, fn ($m) => $m->hasTo($other->user->email) && $m->reason === 'Berisi promosi.');

        // Penulis melihat alasannya di halaman vendor.
        $this->actingAs($other->user)->get(self::PAGE)->assertSee('tidak ditampilkan')->assertSee('Alasan: Berisi promosi.');
        // Visitor tidak bisa membuka admin ulasan.
        $this->get('/admin/ulasan')->assertForbidden();
    }
}
