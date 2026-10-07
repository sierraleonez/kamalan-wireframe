<?php

namespace App\Filament\Resources\Areas;

use App\Filament\Resources\Areas\Pages\ManageAreas;
use App\Models\Area;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AreaResource extends Resource
{
    protected static ?string $model = Area::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Data acuan';

    protected static ?string $modelLabel = 'area';

    protected static ?string $pluralModelLabel = 'Area';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(60),
            TextInput::make('short')->label('Nama pendek')->helperText('Mis. Jaksel. Dipakai di chip dan judul pendek.')->required()->maxLength(20),
            TextInput::make('slug')->required()->maxLength(60)->unique(ignoreRecord: true)
                ->regex('/^[a-z0-9-]+$/')->helperText('Bagian URL, mis. jakarta-selatan. Mengubahnya memutus tautan lama.'),
            TagsInput::make('hoods')->label('Kawasan')->helperText('Kawasan di area ini, untuk isian kawasan vendor.')->required()->default([]),
            Select::make('near')->label('Area terdekat')->multiple()
                ->options(fn (?Area $record) => Area::orderBy('sort')->when($record, fn ($query) => $query->whereKeyNot($record->id))->pluck('name', 'slug'))
                ->helperText('Ditawarkan saat hasil di area ini sedikit.')->default([]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('short')->label('Pendek'),
                TextColumn::make('slug'),
                TextColumn::make('vendors_count')->label('Vendor')->counts('vendors'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Area $record) => $record->vendors()->exists() || $record->hasDependents())
                    ->tooltip('Area yang masih dipakai tidak bisa dihapus'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAreas::route('/')];
    }
}
