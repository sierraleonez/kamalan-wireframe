<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Catalog\Pages;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SiteTest extends TestCase
{
    public static function routes(): array
    {
        return [
            ['/', 'Acara yang'],
            ['/wedding', 'Vendor pernikahan dengan harga yang kami tanyakan sendiri.'],
            ['/corporate', 'Vendor acara kantor'],
            ['/ulang-tahun', 'Vendor ulang tahun'],
            ['/baby-kids', 'Vendor acara bayi &amp; anak'],
            ['/social-gathering', 'Vendor kumpul-kumpul'],
            ['/perayaan', 'Vendor perayaan'],
            ['/wedding/venue', 'Venue pernikahan di Jabodetabek'],
            ['/wedding/venue/jakarta-selatan', 'Venue pernikahan di Jakarta Selatan'],
            ['/ulang-tahun/venue/jakarta-selatan', 'Venue ulang tahun di Jakarta Selatan'],
            ['/baby-kids/dekorasi', 'Dekorasi acara bayi &amp; anak di Jabodetabek'],
            ['/corporate/eo/tangerang', 'EO acara kantor di Tangerang'],
            ['/wedding/venue/ballroom-kebayoran', 'Ballroom Kebayoran'],
            ['/corporate/venue/ballroom-kebayoran', 'Ballroom Kebayoran'],
            ['/wedding/bundle/paket-intimate-wedding-150-pax', 'Paket Intimate Wedding 150 pax'],
            ['/bundle', 'Satu kontak, semua vendornya'],
            ['/perayaan/bundle', 'Belum ada paket untuk acara ini'],
            ['/koleksi', 'Sudah kami pilihkan'],
            ['/koleksi/venue-town-hall-500-parkir-bus', 'venue town hall'],
            ['/tersimpan', 'Yang kamu simpan'],
            ['/kasih-tau-kami', 'Ngga nemu yang kamu cari?'],
        ];
    }

    #[DataProvider('routes')]
    public function test_route_renders(string $path, string $h1): void
    {
        $this->get($path)->assertOk()->assertSee($h1, false)->assertSee('<em>Kamalan</em>', false);
    }

    public function test_home_follows_the_approved_page(): void
    {
        $res = $this->get('/')->assertOk()
            ->assertSee('<title>Kamalan Event Hub — Vendor acara di Jabodetabek</title>', false)
            ->assertSeeInOrder(['Acaranya yang mana?', 'Cari per kebutuhan', 'Satu kontak, semua vendornya', 'Baru kami datangi', 'Sudah kami pilihkan', 'Daftar yang kami tulis sendiri.', 'Ngga nemu yang kamu cari?']);
        foreach (['Wedding', 'Corporate', 'Ulang Tahun', 'Baby &amp; Kids', 'Social Gathering', 'Perayaan'] as $occ) {
            $res->assertSee('<h3>'.$occ.'</h3>', false);
        }
        // Tidak ada lapis catatan wireframe di situs hi-fi.
        $res->assertDontSee('pen-rail', false)->assertDontSee('Catatan desain');
    }

    public function test_unknown_paths_are_404_with_site_layout(): void
    {
        foreach (['/nope', '/wedding/nope', '/corporate/dekorasi', '/social-gathering/dekorasi', '/wedding/venue/tidak-ada', '/corporate/venue/taman-cilandak', '/koleksi/nope'] as $path) {
            $this->get($path)->assertNotFound()->assertSee('Halaman ini tidak ada.');
        }
    }

    public function test_area_slug_wins_over_vendor_slug(): void
    {
        $this->get('/wedding/venue/depok')->assertOk()->assertSee('Venue pernikahan di Depok');
    }

    public function test_detail_has_one_tab_per_served_occasion_with_the_url_pane_active(): void
    {
        $v = Catalog::vendor('taman-cilandak');
        $this->assertSame(['wedding', 'ulang-tahun', 'social-gathering', 'perayaan'], $v['types']);

        $html = $this->get('/ulang-tahun/venue/taman-cilandak')->assertOk()->getContent();
        $this->assertSame(4, substr_count($html, 'class="occ-tab"'));
        $this->assertMatchesRegularExpression('/data-occ="ulang-tahun"[^>]*>Ulang Tahun|aria-selected="true"[^>]*data-occ="ulang-tahun"/', $html);
        $this->assertStringContainsString('aria-selected="true" :aria-selected="occ === \'ulang-tahun\'"', $html);
        $this->assertMatchesRegularExpression('/data-pane="wedding"\s+hidden/', $html);
        $this->assertDoesNotMatchRegularExpression('/data-pane="ulang-tahun"\s+hidden/', $html);
        $this->assertStringContainsString('Menurut pengelola, venue ini tidak menerima acara Corporate dan Baby &amp; Kids.', $html);
        $this->assertStringContainsString('untuk <span class="ref-occ" x-text="phrase">acara ulang tahun</span>', $html);
    }

    public function test_detail_keeps_orange_for_the_single_whatsapp_cta(): void
    {
        $html = $this->get('/wedding/venue/ballroom-kebayoran')->assertOk()->getContent();
        // Kartu CTA + bar bawah HP; kartu bundle dan serupa memakai tint.
        $this->assertSame(2, substr_count($html, 'btn btn-orange'));
        $this->assertSame(0, substr_count($html, 'class="go wa"'));
        $this->assertStringContainsString('ref <code>VNU-0104-DETAIL</code>', $html);
    }

    public function test_prices_carry_their_exclusions(): void
    {
        $this->get('/wedding/venue/taman-cilandak')->assertSee('Rp 32 juta')->assertSee('Sewa venue 6 jam, belum termasuk catering dan dekorasi');
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

    public function test_non_corporate_occasions_use_the_mood_filters(): void
    {
        $keys = fn ($t) => array_column(\App\Catalog\Filters::defs($t, 'venue'), 'key');
        $this->assertSame($keys('wedding'), $keys('ulang-tahun'));
        $this->assertContains('bus', $keys('corporate'));
    }

    public function test_detail_page_has_share_button_and_link_preview(): void
    {
        $url = url('/wedding/venue/ballroom-kebayoran');
        $this->get('/wedding/venue/ballroom-kebayoran')
            ->assertOk()
            ->assertSee('↗ Bagikan')
            ->assertSee('value="'.$url.'"', false)
            ->assertSee('https://wa.me/?text=Ballroom%20Kebayoran', false)
            ->assertSee('<meta property="og:title" content="Ballroom Kebayoran — Kamalan Event Hub">', false)
            ->assertSee('<meta property="og:url" content="'.$url.'">', false);

        // Tautan bagikan tidak membawa kode referral; hanya tombol hubungi vendor yang membawanya.
        $share = Pages::detail('wedding', Catalog::vendor('ballroom-kebayoran'))['share'];
        $this->assertStringNotContainsString('VNU-', $share['wa']);
        $this->assertStringNotContainsString('?', $share['url']);
    }

    public function test_bundle_page_has_share_button_and_link_preview(): void
    {
        $url = url('/wedding/bundle/paket-intimate-wedding-150-pax');
        $this->get('/wedding/bundle/paket-intimate-wedding-150-pax')
            ->assertOk()
            ->assertSee('↗ Bagikan')
            ->assertSee('value="'.$url.'"', false)
            ->assertSee('https://wa.me/?text=Paket%20Intimate%20Wedding%20150%20pax', false)
            ->assertSee('<meta property="og:url" content="'.$url.'">', false)
            ->assertSee('ref <code>BDL-2100-BUNDLE</code>', false);

        $share = Pages::bundle(Catalog::bundle('paket-intimate-wedding-150-pax'))['share'];
        $this->assertStringContainsString('Venue + Catering + Dekorasi, Jakarta Selatan, Rp 85 – 110 jt, oleh ', $share['text']);
        $this->assertStringNotContainsString('BDL-', $share['wa']);
    }

    public function test_go_redirects_to_whatsapp_with_placement_code(): void
    {
        $this->get('/go/VNU-0104?t=wedding&p=DETAIL')
            ->assertRedirectContains('https://wa.me/')
            ->assertRedirectContains(rawurlencode('Halo, saya dari Kamalan — ref VNU-0104-DETAIL. Saya lihat Ballroom Kebayoran untuk acara pernikahan.'));
        // Penempatan tak dikenal jatuh ke LIST; jenis acara yang tidak dilayani jatuh ke yang pertama.
        $this->get('/go/VNU-0104?t=perayaan&p=evil')->assertRedirectContains('VNU-0104-LIST');
        $this->get('/go/XXX-0000')->assertNotFound();
    }

    public function test_unknown_listing_subpath_is_404(): void
    {
        $this->get('/wedding/venue/hitung')->assertNotFound();
    }

    public function test_saved_data_returns_cards_for_valid_keys_only(): void
    {
        $this->getJson('/tersimpan/data?keys=v:wedding:ballroom-kebayoran,b:wedding:paket-intimate-wedding-150-pax,v:corporate:taman-cilandak,x:y:z')
            ->assertOk()->assertJsonCount(2, 'items')->assertJsonPath('items.1.waLabel', 'Hubungi EO')
            ->assertJsonPath('items.0.wa', '/go/VNU-0104?t=wedding&p=SAVED');
    }

    public function test_demand_form_validates_and_confirms(): void
    {
        $this->post('/kasih-tau-kami', ['jenis' => 'wedding', 'wa' => '123'])->assertSessionHasErrors('wa');
        $this->post('/kasih-tau-kami', ['wa' => '081234567890'])->assertSessionHasErrors('jenis');

        $this->post('/kasih-tau-kami', ['jenis' => 'baby-kids', 'wa' => '0812 3456 7890', 'area' => 'depok'])
            ->assertRedirect('/kasih-tau-kami/terkirim');
        $this->get('/kasih-tau-kami/terkirim')->assertOk()->assertSee('081234567890')->assertSee('Baby &amp; Kids · Depok', false);

        $this->post('/kasih-tau-kami', ['jenis' => 'wedding', 'wa' => '0812 3456 7890', 'area' => 'jakarta-selatan', 'asal' => '/wedding/venue/jakarta-selatan?tipe=outdoor'])
            ->assertRedirect('/kasih-tau-kami/terkirim');
        $this->get('/kasih-tau-kami/terkirim')->assertOk()->assertSee('Lanjut lihat venue pernikahan di Jakarta Selatan');
    }
}
