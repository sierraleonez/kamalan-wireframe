<?php

namespace App\Filament\Resources\Occasions;

use App\Filament\Resources\Occasions\Pages\ManageOccasions;
use App\Models\Occasion;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Jenis acara: daftarnya tetap (Catalog::TYPES), jadi tidak bisa ditambah atau dihapus di sini.
 * Yang bisa diedit: teks, dan kategori apa saja yang tampil beserta judul H1-nya.
 */
class OccasionResource extends Resource
{
    protected static ?string $model = Occasion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Data acuan';

    protected static ?string $modelLabel = 'jenis acara';

    protected static ?string $pluralModelLabel = 'Jenis acara';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(40),
            TextInput::make('guest')->label('Sebutan tamu')->helperText('tamu / peserta')->required()->maxLength(20),
            TextInput::make('noun')->label('Kata benda')->helperText('Mis. "pernikahan" → "Vendor pernikahan yang sudah kami datangi."')->required()->maxLength(40),
            TextInput::make('phrase')->label('Frasa')->helperText('Mis. "acara pernikahan", dipakai di pesan WhatsApp.')->required()->maxLength(40),
            TextInput::make('pane')->label('Judul panel di detail vendor')->required()->maxLength(60),
            TextInput::make('blurb')->label('Ringkasan')->helperText('Satu kalimat di kartu jenis acara.')->required()->maxLength(160)->columnSpanFull(),
            Repeater::make('categoryLinks')
                ->label('Kategori yang tampil')
                ->relationship()
                ->orderColumn('sort')
                ->reorderable()
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('Tambah kategori')
                ->schema([
                    Select::make('category_id')->label('Kategori')->relationship('category', 'name')->required()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    TextInput::make('h1')->label('Judul halaman (H1)')->helperText('Mis. "Venue pernikahan"')->required()->maxLength(80),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Nama'),
                TextColumn::make('slug'),
                TextColumn::make('blurb')->label('Ringkasan')->wrap(),
                TextColumn::make('vendors_count')->label('Vendor')->counts('vendors'),
            ])
            ->recordActions([EditAction::make()->slideOver()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOccasions::route('/')];
    }
}
