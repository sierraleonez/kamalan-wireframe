<?php

namespace App\Filament\Pages;

use App\Catalog\HomeText;
use App\Filament\Support\PhotoField;
use App\Models\Bundle;
use App\Models\Collection;
use App\Models\Setting;
use App\Models\Vendor;
use App\Support\HeroSlides;
use App\Support\ImageStore;
use App\Support\SiteContact;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;

/**
 * Konten beranda yang bukan milik data lain: foto hero, teks (HomeText), dan vendor / paket / koleksi yang tampil
 * (disimpan sebagai daftar id di settings; kosong = otomatis). Foto kartu jenis acara dan kategori
 * diatur di Jenis acara dan Kategori.
 */
class HomeContent extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Konten beranda';

    protected static ?string $title = 'Konten beranda';

    protected static ?string $slug = 'konten-beranda';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->current());
    }

    /** Bagian beranda yang isinya bisa dipilih: kunci setting => [judul, maks., penjelasan]. */
    private const PICKS = [
        'vendors' => ['Vendor Terbaik', 8, 'Kosongkan untuk otomatis: venue wedding, yang dipromosikan dulu, lalu urutan editorial (4 vendor). Vendor pertama yang dipromosikan diberi label Promoted.'],
        'bundles' => ['Best Deals (paket bundle)', 6, 'Kosongkan untuk otomatis: paket yang dipromosikan dulu (3 paket). Bagian ini tersembunyi kalau belum ada paket.'],
        'collections' => ['Koleksi', 6, 'Kosongkan untuk otomatis: 3 koleksi pertama. Bagian ini tersembunyi kalau belum ada koleksi.'],
    ];

    private function current(): array
    {
        $settings = Setting::query()->pluck('value', 'key');
        $data = [
            'hero_slides' => array_map(fn ($s) => ['image' => $s['image'], 'image_url' => null, 'image_alt' => $s['alt']], HeroSlides::fromSettings($settings)),
            'hero_interval' => HeroSlides::interval($settings),
        ];
        foreach (array_keys(self::PICKS) as $key) {
            $data[$key] = json_decode(Setting::value('home.'.$key) ?? '[]', true) ?: [];
        }
        $data['contact'] = SiteContact::fromSettings($settings);
        $data['texts'] = array_merge(HomeText::defaults(), json_decode(Setting::value('home.texts') ?? '[]', true) ?: []);

        return $data;
    }

    /** Pilihan untuk dropdown: id => nama yang mudah dikenali. */
    private static function options(string $key): array
    {
        return match ($key) {
            'vendors' => Vendor::with(['category', 'area'])->orderBy('name')->get()
                ->mapWithKeys(fn ($v) => [$v->id => $v->name.' — '.$v->category?->name.', '.$v->area?->name])->all(),
            'bundles' => Bundle::orderBy('title')->pluck('title', 'id')->all(),
            'collections' => Collection::orderBy('title')->pluck('title', 'id')->all(),
        };
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Hero')
                ->description('Foto besar di samping judul beranda, berganti otomatis kalau lebih dari satu (carousel dengan titik dan panah). Paling bagus foto landscape, minimal 1600 px lebarnya. Seret untuk mengubah urutan. Foto kartu jenis acara diatur di Jenis acara; foto kategori di Kategori.')
                ->components([
                    Repeater::make('hero_slides')->label('Foto')
                        ->schema(PhotoField::make('', true))
                        ->reorderable()->collapsible()->maxItems(HeroSlides::MAX)->defaultItems(0)
                        ->itemLabel(fn (array $state) => filled($state['image_alt'] ?? null) ? $state['image_alt'] : 'Foto')
                        ->addActionLabel('Tambah foto'),
                    TextInput::make('hero_interval')->label('Ganti foto tiap')
                        ->numeric()->integer()->minValue(2)->maxValue(30)->suffix('detik')->required()
                        ->helperText('Carousel berhenti sebentar saat kursor di atas foto. Pengunjung yang mematikan animasi di perangkatnya tidak melihat pergantian otomatis.'),
                ]),
            Section::make('Kontak (footer)')
                ->description('Kolom "Hubungi kami" di footer semua halaman. Isian yang kosong tidak ditampilkan; kalau semuanya kosong, kolomnya tidak muncul.')
                ->collapsible()
                ->columns(2)
                ->components([
                    TextInput::make('contact.wa')->label('WhatsApp')->tel()->placeholder('0812…')->maxLength(30)
                        ->regex('/^[0-9 +()-]*$/')->helperText('Dibuka sebagai chat WhatsApp; 08… otomatis jadi +62.'),
                    TextInput::make('contact.email')->label('Email')->email()->maxLength(120),
                    TextInput::make('contact.ig')->label('Instagram')->placeholder('@kamalan')->maxLength(80),
                    Textarea::make('contact.address')->label('Alamat')->rows(2)->maxLength(300),
                ]),
            Section::make('Teks beranda')
                ->description('Semua tulisan tetap di beranda. Kotak yang dikosongkan kembali ke teks bawaan. Nama dan deskripsi jenis acara diatur di Jenis acara; nama kategori di Kategori.')
                ->collapsible()
                ->components(array_map(fn ($group, $fields) => Fieldset::make($group)->columns(1)->components(array_map(
                    fn ($key, $f) => ($f[2] ? Textarea::make('texts.'.$key)->rows(3) : TextInput::make('texts.'.$key))
                        ->label($f[0])->placeholder($f[1])->maxLength(400),
                    array_keys($fields), $fields,
                )), array_keys(HomeText::FIELDS), HomeText::FIELDS)),
            ...array_map(fn ($key, $cfg) => Section::make($cfg[0])
                ->description($cfg[2].' Seret untuk mengubah urutan.')
                ->components([
                    Repeater::make($key)->hiddenLabel()
                        ->simple(Select::make('id')->options(fn () => self::options($key))->searchable()->required()->distinct())
                        ->reorderable()->maxItems($cfg[1])->defaultItems(0)
                        ->addActionLabel('Tambah'),
                ]), array_keys(self::PICKS), self::PICKS),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([Action::make('save')->label('Simpan')->submit('save')->keyBindings(['mod+s'])]),
                ]),
        ]);
    }

    public function save(): void
    {
        $old = array_column(HeroSlides::stored(), 'image');
        try {
            $state = $this->form->getState();
            $slides = [];
            foreach ($state['hero_slides'] ?? [] as $item) {
                // Foto yang sudah tersimpan tidak diproses ulang; unggahan atau link baru jadi WebP.
                $path = PhotoField::path($item['image'] ?? null);
                $saved = PhotoField::apply($item, 'beranda-hero', 1600, in_array($path, $old, true) ? $path : null);
                if ($saved['image']) {
                    $slides[] = ['image' => $saved['image'], 'alt' => (string) $saved['image_alt']];
                }
            }
        } catch (Halt) {
            return; // pesan sudah ditampilkan
        }
        foreach (array_diff($old, array_column($slides, 'image')) as $removed) {
            ImageStore::delete($removed);
        }
        Setting::put('home.hero_slides', json_encode($slides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        Setting::put('home.hero_interval', (string) (int) $state['hero_interval']);
        // Format lama (satu foto) sudah pindah ke hero_slides.
        Setting::put('home.hero_image', null);
        Setting::put('home.hero_alt', null);
        foreach (array_keys(self::PICKS) as $key) {
            $ids = array_values(array_unique(array_map('intval', array_filter($state[$key] ?? []))));
            Setting::put('home.'.$key, $ids ? json_encode($ids) : null);
        }
        $contact = SiteContact::fromSettings(['site.contact' => json_encode($state['contact'] ?? [])]);
        Setting::put('site.contact', array_filter($contact) ? json_encode($contact, JSON_UNESCAPED_UNICODE) : null);
        $texts = HomeText::overrides($state['texts'] ?? []);
        Setting::put('home.texts', $texts ? json_encode($texts, JSON_UNESCAPED_UNICODE) : null);

        $this->form->fill($this->current());
        Notification::make()->title('Konten beranda disimpan')->success()->send();
    }
}
