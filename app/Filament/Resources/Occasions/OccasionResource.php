<?php

namespace App\Filament\Resources\Occasions;

use App\Catalog\Catalog;
use App\Filament\Resources\Occasions\Pages\ManageOccasions;
use App\Filament\Support\PhotoField;
use App\Models\Bundle;
use App\Models\Collection;
use App\Models\Occasion;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Jenis acara. Setiap jenis acara wajib punya foto sampul (kartu di beranda) dan minimal satu
 * kategori. Jenis acara baru memakai teks umum di halaman vendor; teks khusus per acara
 * (Copy::occasionNote) hanya ada untuk enam jenis acara awal.
 */
class OccasionResource extends Resource
{
    protected static ?string $model = Occasion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Data acuan';

    protected static ?string $modelLabel = 'jenis acara';

    protected static ?string $pluralModelLabel = 'Jenis acara';

    /** Bisa dihapus selama tidak dipakai bundle atau koleksi (vendor hanya dilepas). */
    public static function canDelete($record): bool
    {
        return ! Bundle::where('occasion_id', $record->id)->exists() && ! Collection::where('occasion_id', $record->id)->exists();
    }

    /** Simpan foto (unggahan/link) sebelum data jenis acara ditulis. */
    public static function withPhoto(array $data, ?Occasion $record): array
    {
        return PhotoField::apply($data, 'acara-'.($data['slug'] ?? $record?->slug), 1000, $record?->image);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(40)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                    if ($operation === 'create' && blank($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')->required()->maxLength(40)->unique(ignoreRecord: true)
                ->regex('/^[a-z0-9-]+$/')->notIn(Catalog::RESERVED)
                ->validationMessages(['not_in' => 'Slug ini dipakai halaman lain situs.'])
                ->disabledOn('edit')->dehydrated(fn (string $operation) => $operation === 'create')
                ->helperText('Bagian URL, mis. /wedding. Tidak bisa diganti setelah dibuat.'),
            TextInput::make('guest')->label('Sebutan tamu')->helperText('tamu / peserta')->required()->maxLength(20),
            TextInput::make('noun')->label('Kata benda')->helperText('Mis. "pernikahan" → "Vendor pernikahan yang sudah kami datangi."')->required()->maxLength(40),
            TextInput::make('phrase')->label('Frasa')->helperText('Mis. "acara pernikahan", dipakai di pesan WhatsApp.')->required()->maxLength(40),
            TextInput::make('pane')->label('Judul panel di detail vendor')->required()->maxLength(60),
            TextInput::make('blurb')->label('Ringkasan')->helperText('Satu kalimat di kartu jenis acara.')->required()->maxLength(160)->columnSpanFull(),
            Section::make('Foto sampul')->description('Latar kartu jenis acara di beranda. Teks putih ditaruh di bagian bawah foto.')
                ->columnSpanFull()->components(PhotoField::make(required: true)),
            Repeater::make('categoryLinks')
                ->label('Kategori yang tampil')
                ->relationship()
                ->orderColumn('sort')
                ->reorderable()
                ->columns(2)
                ->columnSpanFull()
                ->addActionLabel('Tambah kategori')
                ->minItems(1)
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
            ->reorderable('sort')
            ->paginated(false)
            ->columns([
                ImageColumn::make('image')->label('Sampul')->disk('public')->height(40)->width(64),
                TextColumn::make('name')->label('Nama'),
                TextColumn::make('slug'),
                TextColumn::make('blurb')->label('Ringkasan')->wrap(),
                TextColumn::make('vendors_count')->label('Vendor')->counts('vendors'),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->mutateDataUsing(fn (array $data, Occasion $record) => self::withPhoto($data, $record)),
                DeleteAction::make()->modalDescription('Vendor dilepas dari jenis acara ini; halaman /slug-nya jadi 404.'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageOccasions::route('/')];
    }
}
