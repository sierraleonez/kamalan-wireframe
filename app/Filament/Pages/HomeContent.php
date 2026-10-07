<?php

namespace App\Filament\Pages;

use App\Filament\Support\PhotoField;
use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
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
 * Konten beranda yang bukan milik data lain: foto hero. Foto kartu jenis acara dan kategori
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

    private function current(): array
    {
        return ['hero' => [
            'image' => Setting::value('home.hero_image'),
            'image_url' => null,
            'image_alt' => Setting::value('home.hero_alt'),
        ]];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Hero')
                ->description('Foto besar di samping judul beranda. Paling bagus foto landscape, minimal 1600 px lebarnya. Foto kartu jenis acara diatur di Jenis acara; foto kategori di Kategori.')
                ->components(PhotoField::make('hero.')),
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
        try {
            $hero = PhotoField::apply($this->form->getState()['hero'], 'beranda-hero', 1600, Setting::value('home.hero_image'));
        } catch (Halt) {
            return; // pesan sudah ditampilkan
        }
        Setting::put('home.hero_image', $hero['image']);
        Setting::put('home.hero_alt', $hero['image_alt']);

        $this->form->fill($this->current());
        Notification::make()->title('Konten beranda disimpan')->success()->send();
    }
}
