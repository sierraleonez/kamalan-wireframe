<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Occasion;
use App\Models\Setting;
use App\Support\ImageStore;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Foto beranda: hero, kartu jenis acara, dan kartu kategori vendor. Setiap foto bisa diunggah
 * (klik atau seret), atau diambil dari link; semuanya diperkecil dan disimpan sebagai WebP.
 */
class HomeContent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Konten beranda';

    protected static ?string $title = 'Konten beranda';

    protected static ?string $slug = 'konten-beranda';

    /** Lebar maksimum per tempat (px), cukup untuk layar HP 3x. */
    private const WIDTH = ['hero' => 1600, 'occ' => 1000, 'cat' => 500];

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->current());
    }

    /** Isi form dari database. */
    private function current(): array
    {
        $slot = fn (?string $image, ?string $alt) => ['image' => $image, 'url' => null, 'alt' => $alt];

        return [
            'hero' => $slot(Setting::value('home.hero_image'), Setting::value('home.hero_alt')),
            'occ' => Occasion::orderBy('sort')->get()->mapWithKeys(fn ($o) => [$o->slug => $slot($o->image, $o->image_alt)])->all(),
            'cat' => Category::orderBy('sort')->get()->mapWithKeys(fn ($c) => [$c->slug => $slot($c->image, $c->image_alt)])->all(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Hero')
                ->description('Foto besar di samping judul beranda. Paling bagus foto landscape, minimal 1600 px lebarnya.')
                ->components([self::slot('hero', 'Foto hero')]),
            Section::make('Jenis acara')
                ->description('Latar kartu "Acaranya yang mana?". Teks putih ditaruh di bagian bawah foto.')
                ->collapsible()
                ->components([Grid::make(['default' => 1, 'lg' => 2])->components(
                    Occasion::orderBy('sort')->get()->map(fn ($o) => self::slot('occ.'.$o->slug, $o->name))->all()
                )]),
            Section::make('Kategori vendor')
                ->description('Kotak di "Cari per kebutuhan" (beranda) dan di beranda tiap jenis acara. Foto dipotong persegi panjang pendek, jadi pilih yang objeknya di tengah.')
                ->collapsible()
                ->components([Grid::make(['default' => 1, 'lg' => 2])->components(
                    Category::orderBy('sort')->get()->map(fn ($c) => self::slot('cat.'.$c->slug, $c->name))->all()
                )]),
        ]);
    }

    /** Satu tempat foto: unggah/seret, atau tempel link, plus teks alternatif. */
    private static function slot(string $key, string $label): Fieldset
    {
        return Fieldset::make($label)->columns(1)->components([
            FileUpload::make($key.'.image')
                ->hiddenLabel()
                ->image()
                ->disk('public')
                ->directory(ImageStore::DIR.'/uploads')
                ->maxSize(15 * 1024)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                ->imagePreviewHeight('160')
                ->helperText('Klik atau seret foto ke sini (JPG, PNG, WebP; maks. 15 MB).')
                ->hintAction(
                    Action::make('copy')
                        ->label('Salin link')
                        ->icon(Heroicon::OutlinedClipboardDocument)
                        ->visible(fn ($state) => filled(self::path($state)))
                        ->alpineClickHandler(fn ($state) => 'window.navigator.clipboard.writeText('.Js::from(url(ImageStore::url(self::path($state)))).'); $tooltip('.Js::from('Link disalin').', { timeout: 1500 })'),
                ),
            TextInput::make($key.'.url')
                ->label('Atau tempel link gambar')
                ->placeholder('https://…')
                ->url()
                ->helperText('Gambar diunduh dan disimpan di server saat disimpan, lalu menggantikan foto di atas.'),
            TextInput::make($key.'.alt')
                ->label('Keterangan foto')
                ->maxLength(150)
                ->helperText('Untuk pembaca layar dan Google, mis. "Resepsi di taman saat sore".'),
        ]);
    }

    /** State FileUpload bisa string atau array [uuid => path]. */
    private static function path(mixed $state): ?string
    {
        return is_array($state) ? (array_values($state)[0] ?? null) : $state;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Simpan')->submit('save')->keyBindings(['mod+s'])])->sticky(),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $before = $this->current();
        $errors = [];

        $slots = [['hero', null, $state['hero']]];
        foreach ($state['occ'] ?? [] as $slug => $s) {
            $slots[] = ['occ', $slug, $s];
        }
        foreach ($state['cat'] ?? [] as $slug => $s) {
            $slots[] = ['cat', $slug, $s];
        }

        foreach ($slots as [$kind, $slug, $s]) {
            $old = $slug === null ? $before[$kind]['image'] : ($before[$kind][$slug]['image'] ?? null);
            $new = self::path($s['image'] ?? null);
            $name = ['hero' => 'beranda-hero', 'occ' => 'acara-'.$slug, 'cat' => 'kategori-'.$slug][$kind];
            $field = 'data.'.$kind.($slug ? '.'.$slug : '');

            try {
                if (filled($s['url'] ?? null)) {
                    $new = ImageStore::fromUrl(trim($s['url']), $name, self::WIDTH[$kind]);
                } elseif ($new && $new !== $old) {
                    $new = ImageStore::fromUpload($new, $name, self::WIDTH[$kind]);
                }
            } catch (RuntimeException $e) {
                $errors[$field.(filled($s['url'] ?? null) ? '.url' : '.image')] = $e->getMessage();

                continue;
            }

            if ($new !== $old) {
                ImageStore::delete($old);
            }
            $alt = filled($s['alt'] ?? null) ? trim($s['alt']) : null;

            match ($kind) {
                'hero' => [Setting::put('home.hero_image', $new), Setting::put('home.hero_alt', $alt)],
                'occ' => Occasion::where('slug', $slug)->first()?->update(['image' => $new, 'image_alt' => $alt]),
                'cat' => Category::where('slug', $slug)->first()?->update(['image' => $new, 'image_alt' => $alt]),
            };
        }

        $this->form->fill($this->current());

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        Notification::make()->title('Konten beranda disimpan')->success()->send();
    }
}
