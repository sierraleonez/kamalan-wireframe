<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\ComingSoon;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Mode Segera hadir: satu layar pengumuman untuk semua pengunjung, admin tetap melihat situs. */
class ComingSoonSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Segera hadir';

    protected static ?string $title = 'Segera hadir';

    protected static ?string $slug = 'segera-hadir';

    public ?array $data = [];

    public static function getNavigationBadge(): ?string
    {
        return ComingSoon::active() ? 'Aktif' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        $this->form->fill(ComingSoon::fromSettings(Setting::query()->pluck('value', 'key')));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Mode Segera hadir')
                ->description('Kalau aktif, semua halaman situs menampilkan layar Segera hadir untuk pengunjung. Kamu (admin yang masuk) tetap melihat situs biasa dengan pita pengingat, dan /admin selalu bisa dibuka.')
                ->components([
                    Toggle::make('enabled')->label('Aktifkan layar Segera hadir')->onColor('warning'),
                ]),
            Section::make('Isi layar')->columns(2)->components([
                TextInput::make('title')->label('Judul')->required()->maxLength(80)->columnSpanFull(),
                Textarea::make('text')->label('Pesan')->required()->rows(3)->maxLength(500)->columnSpanFull()
                    ->helperText('Enter = pindah baris.'),
                TextInput::make('launch')->label('Rencana buka (opsional)')->maxLength(60)->placeholder('mis. Desember 2026')
                    ->helperText('Teks bebas; kosongkan kalau belum pasti.'),
                Toggle::make('show_contact')->label('Tampilkan tombol kontak')->helperText('WhatsApp, email, dan Instagram dari Konten beranda → Kontak.')->inline(false),
                Toggle::make('show_photo')->label('Tampilkan foto hero')->helperText('Foto pertama dari Konten beranda → Hero.')->inline(false),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Simpan')->submit('save')->keyBindings(['mod+s']),
                        Action::make('preview')->label('Lihat layarnya')->color('gray')->url('/?segera-hadir=1')->openUrlInNewTab(),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $data = array_merge(ComingSoon::DEFAULTS, array_intersect_key($state, ComingSoon::DEFAULTS));
        $data['enabled'] = (bool) $data['enabled'];
        $data['show_contact'] = (bool) $data['show_contact'];
        $data['show_photo'] = (bool) $data['show_photo'];
        $data['launch'] = trim((string) $data['launch']);
        Setting::put('site.coming_soon', json_encode($data, JSON_UNESCAPED_UNICODE));

        Notification::make()
            ->title($data['enabled'] ? 'Disimpan. Pengunjung sekarang melihat layar Segera hadir.' : 'Disimpan. Situs terbuka untuk pengunjung.')
            ->success()->send();
    }
}
