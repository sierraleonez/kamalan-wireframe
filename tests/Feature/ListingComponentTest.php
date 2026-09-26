<?php

namespace Tests\Feature;

use App\Livewire\Listing;
use Livewire\Livewire;
use Tests\TestCase;

class ListingComponentTest extends TestCase
{
    public function test_filters_update_results_and_url_state(): void
    {
        Livewire::test(Listing::class, ['type' => 'wedding', 'cat' => 'venue', 'area' => 'jakarta-selatan'])
            ->assertSee('16</b> venue', false)
            ->set('tipe', 'outdoor')
            ->assertSee('10</b> venue', false)
            ->assertSee('Outdoor ✕')
            ->call('remove', 'tipe')
            ->assertSee('16</b> venue', false);
    }

    public function test_sheet_counts_draft_and_applies_via_navigation(): void
    {
        Livewire::test(Listing::class, ['type' => 'wedding', 'cat' => 'venue', 'area' => 'jakarta-selatan'])
            ->call('openSheet')
            ->assertSee('Tampilkan 16 hasil')
            ->call('sheetBand', 'tipe', 'outdoor')
            ->assertSee('Tampilkan 10 hasil')
            ->call('applySheet')
            ->assertRedirect('/wedding/venue/jakarta-selatan?tipe=outdoor');
    }

    public function test_load_more(): void
    {
        Livewire::test(Listing::class, ['type' => 'wedding', 'cat' => 'venue', 'area' => 'jakarta-selatan'])
            ->assertSee('9 dari 16')
            ->call('more')
            ->assertDontSee('dari 16');
    }
}
