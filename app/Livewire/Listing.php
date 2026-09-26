<?php

namespace App\Livewire;

use App\Catalog\Catalog;
use App\Catalog\Filters;
use App\Catalog\Pages;
use App\Catalog\Present;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Listing kategori (frontend Blade). Filter tersimpan di query string lewat #[Url],
 * jadi setiap keadaan, termasuk hasil kosong, tetap bisa dibagikan.
 */
class Listing extends Component
{
    #[Locked]
    public string $type;

    #[Locked]
    public string $cat;

    #[Locked]
    public ?string $area = null;

    #[Url(except: '')]
    public string $kapasitas = '';

    #[Url(except: '')]
    public string $harga = '';

    #[Url(except: '')]
    public string $tipe = '';

    #[Url(as: 'catering-bebas', except: '')]
    public string $cateringBebas = '';

    #[Url(as: 'ruang-transit', except: '')]
    public string $ruangTransit = '';

    #[Url(as: 'parkir-100', except: '')]
    public string $parkir100 = '';

    #[Url(except: '')]
    public string $halal = '';

    #[Url(except: '')]
    public string $av = '';

    #[Url(except: '')]
    public string $bus = '';

    #[Url(except: '')]
    public string $pkp = '';

    #[Url(except: '')]
    public string $urut = '';

    public int $shown = Pages::PER_PAGE;

    /** Sheet filter mobile: draf yang belum diterapkan. */
    public bool $sheet = false;

    public ?string $draftArea = null;

    public array $draft = [];

    /** kunci query → nama properti */
    public const PROPS = [
        'kapasitas' => 'kapasitas', 'harga' => 'harga', 'tipe' => 'tipe',
        'catering-bebas' => 'cateringBebas', 'ruang-transit' => 'ruangTransit', 'parkir-100' => 'parkir100',
        'halal' => 'halal', 'av' => 'av', 'bus' => 'bus', 'pkp' => 'pkp',
    ];

    private function query(): array
    {
        $q = [];
        foreach (self::PROPS as $key => $prop) {
            if ($this->{$prop} !== '') {
                $q[$key] = $this->{$prop};
            }
        }
        if ($this->urut !== '') {
            $q['urut'] = $this->urut;
        }

        return $q;
    }

    public function updated(string $name): void
    {
        if (in_array($name, self::PROPS, true) || $name === 'urut') {
            $this->shown = Pages::PER_PAGE;
            if ($name === 'urut' && $this->urut === 'editor') {
                $this->urut = '';
            }
        }
    }

    public function toggle(string $key): void
    {
        if (isset(self::PROPS[$key])) {
            $prop = self::PROPS[$key];
            $this->{$prop} = $this->{$prop} === '1' ? '' : '1';
            $this->shown = Pages::PER_PAGE;
        }
    }

    public function remove(string $key): void
    {
        if (isset(self::PROPS[$key])) {
            $this->{self::PROPS[$key]} = '';
            $this->shown = Pages::PER_PAGE;
        }
    }

    public function more(): void
    {
        $this->shown += Pages::PER_PAGE;
    }

    public function openSheet(): void
    {
        $this->draft = array_diff_key($this->query(), ['urut' => 1]);
        $this->draftArea = $this->area;
        $this->sheet = true;
    }

    public function closeSheet(): void
    {
        $this->sheet = false;
    }

    public function sheetReset(): void
    {
        $this->draft = [];
        $this->draftArea = null;
    }

    public function sheetArea(string $slug): void
    {
        $this->draftArea = $this->draftArea === $slug || ! Catalog::area($slug) ? null : $slug;
    }

    public function sheetBand(string $key, string $id): void
    {
        if (($this->draft[$key] ?? null) === $id) {
            unset($this->draft[$key]);
        } else {
            $this->draft[$key] = $id;
        }
    }

    public function sheetFlag(string $key): void
    {
        if (($this->draft[$key] ?? null) === '1') {
            unset($this->draft[$key]);
        } else {
            $this->draft[$key] = '1';
        }
    }

    public function applySheet(): void
    {
        $q = Filters::normalize(Filters::defs($this->type, $this->cat), $this->draft);
        if ($this->urut !== '') {
            $q['urut'] = $this->urut;
        }
        $this->sheet = false;
        $this->redirect(Present::listingPath($this->type, $this->cat, $this->draftArea, $q), navigate: true);
    }

    public function render()
    {
        $p = Pages::listing($this->type, $this->cat, $this->area, $this->query(), $this->shown);
        $sheetCount = $this->sheet
            ? Pages::countFor($this->type, $this->cat, $this->draftArea, Filters::normalize(Filters::defs($this->type, $this->cat), $this->draft))
            : null;

        return view('livewire.listing', ['p' => $p, 'props' => self::PROPS, 'sheetCount' => $sheetCount]);
    }
}
