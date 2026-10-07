<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kategori vendor. Daftarnya tetap: filter, satuan harga, dan tulisan per kategori ada di kode.
 * Judul H1 per jenis acara diatur di Jenis acara.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Data acuan';

    protected static ?string $modelLabel = 'kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

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
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(40),
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
                TextColumn::make('code')->label('Kode ref'),
                TextColumn::make('unit')->label('Satuan harga')->formatStateUsing(fn ($state, Category $record) => $record->priceUnitLabel()),
                TextColumn::make('vendors_count')->label('Vendor')->counts('vendors'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
