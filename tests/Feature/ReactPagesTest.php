<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Frontend React menerima komponen Inertia dan props yang sama dengan Blade. */
class ReactPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend' => 'react', 'inertia.ssr.enabled' => false]);
    }

    public static function pages(): array
    {
        return [
            ['/', 'Home'],
            ['/corporate', 'Branch'],
            ['/wedding/venue/jakarta-selatan', 'Listing'],
            ['/wedding/venue/ballroom-kebayoran', 'Detail'],
            ['/wedding/bundle/paket-intimate-wedding-150-pax', 'Bundle'],
            ['/bundle', 'Bundles'],
            ['/koleksi', 'Collections'],
            ['/koleksi/rooftop-jaksel-dibawah-50jt', 'Collection'],
            ['/tersimpan', 'Saved'],
            ['/kasih-tau-kami', 'Form'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_component(string $path, string $component): void
    {
        $this->get($path)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component)->has('title')->has('notes')->has('footer'));
    }

    public function test_zero_state_props(): void
    {
        $this->get('/wedding/venue/jakarta-selatan?kapasitas=500-plus&harga=lt30&tipe=outdoor')
            ->assertInertia(fn (Assert $page) => $page->component('Listing')
                ->where('zero', true)
                ->where('relaxations.0.strong', 'kapasitas: 500+')
                ->where('prefill.tamu', '500+')
                ->has('formOptions.areas'));
    }

    public function test_not_found_uses_react_layout(): void
    {
        $this->get('/tidak-ada')->assertNotFound()->assertInertia(fn (Assert $page) => $page->component('NotFound')->has('footer'));
    }

    public function test_load_more_via_query(): void
    {
        $this->get('/wedding/venue/jakarta-selatan?tampil=18')
            ->assertInertia(fn (Assert $page) => $page->where('shown', 16)->has('results', 16));
    }
}
