<?php

namespace App\Filament\Resources\Visitors;

use App\Filament\Resources\Visitors\Pages\ManageVisitors;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Akun pengunjung situs (bukan admin): siapa, cara masuk, berapa yang disimpan. Hapus = permintaan hapus data. */
class VisitorResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'pengunjung';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengunjung';

    protected static ?string $modelLabel = 'pengunjung';

    protected static ?string $pluralModelLabel = 'Akun pengunjung';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_admin', false)->withCount('saves');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama')->placeholder('(belum diisi)')->searchable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('joined_via')->label('Masuk lewat')->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'google' ? 'Google' : 'Email'),
                TextColumn::make('saves_count')->label('Disimpan')->sortable(),
                TextColumn::make('created_at')->label('Bergabung')->dateTime('j M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('joined_via')->label('Masuk lewat')->options(['google' => 'Google', 'email' => 'Email']),
            ])
            ->recordActions([
                DeleteAction::make()->modalDescription('Akun dan semua yang disimpannya dihapus. Pakai ini untuk permintaan hapus data.'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVisitors::route('/')];
    }
}
