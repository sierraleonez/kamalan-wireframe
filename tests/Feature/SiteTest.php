<?php

namespace Tests\Feature;

use App\Catalog\Pages;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteTest extends TestCase
{
    public static function routes(): array
    {
        return [
            ['/', 'Vendor acara di Jabodetabek'],
            ['/wedding', 'Vendor pernikahan di Jabodetabek'],
            ['/corporate', 'Vendor acara kantor'],
            ['/wedding/venue', 'Venue pernikahan di Jabodetabek'],
            ['/wedding/venue/jakarta-selatan', 'Venue pernikahan di Jakarta Selatan'],
            ['/corporate/eo/tangerang', 'EO acara kantor di Tangerang'],
            ['/wedding/venue/ballroom-kebayoran', 'Ballroom Kebayoran'],
            ['/corporate/venue/ballroom-kebayoran', 'Ballroom Kebayoran'],
            ['/wedding/bundle/paket-intimate-wedding-150-pax', 'Paket Intimate Wedding 150 pax'],
            ['/bundle', 'Paket bundle'],
            ['/corporate/bundle', 'Paket bundle corporate'],
            ['/koleksi', 'Koleksi'],
            ['/koleksi/venue-town-hall-500-parkir-bus', 'venue town hall'],
            ['/tersimpan', 'Yang kamu simpan'],
            ['/kasih-tau-kami', 'Ngga nemu yang kamu cari?'],
        ];
    }

    #[DataProvider('routes')]
    public function test_route_renders(string $path, string $h1): void
    {
        $this->get($path)->assertOk()->assertSee($h1);
    }

    public function test_unknown_paths_are_404_with_site_layout(): void
    {
        foreach (['/nope', '/wedding/nope', '/corporate/dekorasi', '/wedding/venue/tidak-ada', '/corporate/venue/taman-cilandak', '/koleksi/nope'] as $path) {
            $this->get($path)->assertNotFound()->assertSee('Halaman ini tidak ada.');
        }
    }

    public function test_area_slug_wins_over_vendor_slug(): void
    {
        $this->get('/wedding/venue/depok')->assertOk()->assertSee('Venue pernikahan di Depok');
    }

    public function test_promoted_vendor_is_pinned_first_and_labelled(): void
    {
        $p = Pages::listing('wedding', 'venue', 'jakarta-selatan', ['urut' => 'harga']);
        $this->assertSame('Ballroom Kebayoran', $p['results'][0]['name']);
        $this->assertTrue($p['results'][0]['promo']);
    }

    public function test_zero_result_state_offers_relaxations_and_prefilled_form(): void
    {
        $p = Pages::listing('wedding', 'venue', 'jakarta-selatan', ['kapasitas' => '500-plus', 'harga' => 'lt30', 'tipe' => 'outdoor']);
        $this->assertTrue($p['zero']);
        $this->assertSame('kapasitas: 500+', $p['relaxations'][0]['strong']);
        $this->assertSame(2, $p['relaxations'][0]['n']);
        $this->assertSame('500+', $p['prefill']['tamu']);
        $this->assertSame('di bawah 30 jt', $p['prefill']['budget']);

        $this->get('/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor')
            ->assertOk()->assertSee('Belum ada venue pernikahan')->assertSee('Atau biar kami yang carikan.');
    }

    public function test_unknown_filter_values_are_ignored(): void
    {
        $p = Pages::listing('wedding', 'venue', 'jakarta-selatan', ['kapasitas' => 'banyak', 'pkp' => '1']);
        $this->assertSame(16, $p['total']);
        $this->assertSame(['area'], array_column($p['chips'], 'key'));
    }

    public function test_go_redirects_only_to_whatsapp(): void
    {
        $this->get('/go/VNU-0104?t=wedding')->assertRedirectContains('https://wa.me/')->assertRedirectContains('VNU-0104');
        $this->get('/go/XXX-0000')->assertNotFound();
    }

    public function test_unknown_listing_subpath_is_404(): void
    {
        $this->get('/wedding/venue/hitung')->assertNotFound();
    }

    public function test_saved_data_returns_cards_for_valid_keys_only(): void
    {
        $this->getJson('/tersimpan/data?keys=v:wedding:ballroom-kebayoran,b:wedding:paket-intimate-wedding-150-pax,v:corporate:taman-cilandak,x:y:z')
            ->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('items.1.waLabel', 'Hubungi EO');
    }

    public function test_demand_form_validates_and_confirms(): void
    {
        $this->post('/kasih-tau-kami', ['jenis' => 'wedding', 'wa' => '123'])->assertSessionHasErrors('wa');
        $this->post('/kasih-tau-kami', ['wa' => '081234567890'])->assertSessionHasErrors('jenis');

        $this->post('/kasih-tau-kami', ['jenis' => 'wedding', 'wa' => '0812 3456 7890', 'area' => 'jakarta-selatan', 'asal' => '/wedding/venue/jakarta-selatan?tipe=outdoor'])
            ->assertRedirect('/kasih-tau-kami/terkirim');
        $this->get('/kasih-tau-kami/terkirim')->assertOk()->assertSee('081234567890')->assertSee('Lanjut lihat venue pernikahan di Jakarta Selatan');
    }
}
