<?php

namespace Tests\Feature;

use App\Catalog\Catalog;
use App\Models\Bundle;
use App\Models\Lead;
use App\Models\Vendor;
use Tests\TestCase;

class CatalogDatabaseTest extends TestCase
{
    /** Katalog dari database sama persis dengan indeks catalog.json lama (selain kunci opsional bernilai kosong). */
    public function test_built_catalog_matches_the_json_export(): void
    {
        $raw = json_decode(file_get_contents(resource_path('data/catalog.json')), true);
        $built = Catalog::build();

        $strip = fn (array $row) => array_filter($row, fn ($v) => $v !== null && $v !== false);
        $bySlug = fn (array $list) => array_column($list, null, 'slug');

        $this->assertSame($raw['types'], $built['types']);
        $this->assertSame($raw['categories'], $built['categories']);
        foreach ($raw['areas'] as $i => $a) {
            $this->assertSame($a + ['order' => $i], $built['areas'][$a['slug']]);
        }

        foreach (['vendors', 'bundles', 'collections'] as $kind) {
            $old = $bySlug($raw[$kind]);
            $this->assertSame(array_keys($old), array_keys($built[$kind]), $kind.' order');
            foreach ($old as $slug => $row) {
                // Hanya kolom ekspor lama; kolom baru (teks vendor, rincian) diuji terpisah.
                $this->assertEquals($strip($row), $strip(array_intersect_key($built[$kind][$slug], $row)), "$kind.$slug");
            }
        }
    }

    public function test_form_submission_is_saved_as_a_lead(): void
    {
        $this->post('/kasih-tau-kami', [
            'jenis' => 'wedding', 'wa' => '0812 3456 7890', 'kategori' => 'venue', 'area' => 'jakarta-selatan',
            'tamu' => '300', 'cari' => 'Venue outdoor dengan rencana hujan',
        ])->assertRedirect('/kasih-tau-kami/terkirim');

        $lead = Lead::sole();
        $this->assertSame('wedding', $lead->jenis);
        $this->assertSame('081234567890', $lead->wa);
        $this->assertSame('Venue outdoor dengan rencana hujan', $lead->cari);
        $this->assertNull($lead->handled_at);
    }

    public function test_invalid_form_saves_nothing(): void
    {
        $this->post('/kasih-tau-kami', ['jenis' => 'wedding', 'wa' => '123'])->assertSessionHasErrors('wa');
        $this->assertSame(0, Lead::count());
    }

    public function test_vendor_edit_shows_on_the_site(): void
    {
        $this->get('/wedding/venue/ballroom-kebayoran')->assertSee('Ballroom Kebayoran');

        Vendor::where('slug', 'ballroom-kebayoran')->sole()->update([
            'name' => 'Ballroom Kebayoran Raya',
            'writeup' => "Paragraf pertama dari tim.\n\nParagraf kedua dari tim.",
        ]);

        $this->get('/wedding/venue/ballroom-kebayoran')
            ->assertSee('Ballroom Kebayoran Raya')
            ->assertSee('Paragraf pertama dari tim.')
            ->assertSee('Paragraf kedua dari tim.');
    }

    public function test_deleted_vendor_disappears_and_its_bundle_still_renders(): void
    {
        Vendor::where('slug', 'taman-cilandak')->sole()->delete();

        $this->get('/wedding/venue/taman-cilandak')->assertNotFound();
        $this->get('/wedding/bundle/paket-intimate-wedding-150-pax')->assertOk();
    }

    public function test_new_vendor_gets_a_referral_code_from_its_category(): void
    {
        $v = Vendor::create([
            'slug' => 'gedung-baru', 'name' => 'Gedung Baru', 'category_id' => 1, 'area_id' => 1, 'hood' => 'Kebayoran Baru',
            'cap_min' => 100, 'cap_max' => 300, 'price' => 40, 'setting' => 'indoor', 'wa' => '628120000000',
        ]);
        $v->occasions()->sync([1]);
        Catalog::flush();

        $this->assertMatchesRegularExpression('/^VNU-\d{4}$/', $v->fresh()->ref);
        $this->get('/wedding/venue/gedung-baru')->assertOk()->assertSee('Gedung Baru');
    }

    public function test_existing_referral_links_still_redirect(): void
    {
        $this->get('/go/VNU-0104?t=corporate&p=DETAIL')
            ->assertRedirectContains('wa.me/628122247750')
            ->assertRedirectContains('VNU-0104-DETAIL');

        $bundle = Bundle::where('slug', 'paket-intimate-wedding-150-pax')->sole();
        $this->get('/go/'.$bundle->ref.'?p=BUNDLE')->assertRedirectContains('wa.me/'.$bundle->wa);
        $this->get('/go/VNU-9999')->assertNotFound();
    }
}
