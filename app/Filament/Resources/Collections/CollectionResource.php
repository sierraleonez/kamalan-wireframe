<?php

namespace App\Filament\Resources\Collections;

use App\Filament\Resources\Collections\Pages\CreateCollection;
use App\Filament\Resources\Collections\Pages\EditCollection;
use App\Filament\Resources\Collections\Pages\ListCollections;
use App\Models\Collection;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Koleksi kurasi di /koleksi/{slug}: daftar vendor pilihan tim dengan pengantar. */
class CollectionResource extends Resource
{
    protected static ?string $model = Collection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmarkSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'koleksi';

    protected static ?string $pluralModelLabel = 'Koleksi';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Koleksi')->columns(2)->components([
                TextInput::make('title')->label('Judul')->required()->maxLength(160)->columnSpanFull(),
                TextInput::make('short')->label('Judul pendek')->required()->maxLength(60)->helperText('Untuk tautan dan chip.'),
                TextInput::make('slug')->required()->maxLength(120)->unique(ignoreRecord: true)->regex('/^[a-z0-9-]+$/')
                    ->helperText('Bagian URL /koleksi/…'),
                Select::make('occasion_id')->label('Jenis acara')->relationship('occasion', 'name', fn (Builder $query) => $query->orderBy('sort'))->required()->live(),
                Select::make('category_id')->label('Kategori')->relationship('category', 'name')->required()->live(),
                Select::make('area_id')->label('Area')->relationship('area', 'name', fn (Builder $query) => $query->orderBy('sort'))->placeholder('Seluruh Jabodetabek'),
                TextInput::make('sponsor')->label('Sponsor')->maxLength(80)->helperText('Kosongkan kalau bukan koleksi bersponsor.'),
                TagsInput::make('locks')->label('Kriteria')->reorderable()->columnSpanFull()->default([])
                    ->helperText('Label kriteria yang tampil di atas daftar, mis. Rooftop, Jakarta Selatan, di bawah 50 jt.'),
                Textarea::make('intro')->label('Pengantar')->required()->rows(5)->columnSpanFull(),
            ]),

            Section::make('Vendor pilihan')->description('Urutan di sini adalah urutan di halaman.')->components([
                Repeater::make('picks')->hiddenLabel()->relationship()->orderColumn('sort')->reorderable()
                    ->addActionLabel('Tambah vendor')->minItems(1)
                    ->schema([
                        Select::make('vendor_id')->label('Vendor')->required()->searchable()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->options(fn (Get $get) => Vendor::query()
                                ->when($get('../../category_id'), fn ($query, $c) => $query->where('category_id', $c))
                                ->when($get('../../occasion_id'), fn ($query, $o) => $query->whereHas('occasions', fn ($w) => $w->whereKey($o)))
                                ->orderBy('name')->pluck('name', 'id')),
                    ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['occasion', 'category', 'area'])->withCount('picks'))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->wrap(),
                TextColumn::make('occasion.name')->label('Jenis acara')->badge(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('area.name')->label('Area')->placeholder('Jabodetabek'),
                TextColumn::make('picks_count')->label('Vendor'),
                TextColumn::make('sponsor')->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('occasion')->label('Jenis acara')->relationship('occasion', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('site')->label('Lihat')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Collection $record) => url('/koleksi/'.$record->slug))->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCollections::route('/'),
            'create' => CreateCollection::route('/create'),
            'edit' => EditCollection::route('/{record}/edit'),
        ];
    }
}
