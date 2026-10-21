<?php

namespace App\Filament\Resources\VendorApplications;

use App\Catalog\Catalog;
use App\Filament\Resources\VendorApplications\Pages\ManageVendorApplications;
use App\Models\VendorApplication;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Pendaftaran vendor dari /daftarkan-usaha: dibaca, dihubungi, ditandai sudah ditangani. */
class VendorApplicationResource extends Resource
{
    protected static ?string $model = VendorApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengunjung';

    protected static ?string $modelLabel = 'pendaftaran vendor';

    protected static ?string $pluralModelLabel = 'Pendaftaran vendor';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = VendorApplication::whereNull('handled_at')->count();

        return $open ? (string) $open : null;
    }

    public static function categoryName(?string $slug): string
    {
        return Catalog::category((string) $slug)['name'] ?? 'Lainnya';
    }

    public static function areaName(?string $slug): string
    {
        return Catalog::area($slug)['name'] ?? 'Di luar Jabodetabek';
    }

    private static function waUrl(string $wa): string
    {
        return 'https://wa.me/'.preg_replace('/^0/', '62', ltrim($wa, '+'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('business_name')->label('Nama usaha'),
            TextEntry::make('contact_name')->label('Nama kontak'),
            TextEntry::make('wa')->label('WhatsApp')->url(fn (VendorApplication $record) => self::waUrl($record->wa))->openUrlInNewTab()->copyable(),
            TextEntry::make('category')->label('Kategori')->formatStateUsing(fn ($state) => self::categoryName($state)),
            TextEntry::make('area')->formatStateUsing(fn ($state) => self::areaName($state)),
            TextEntry::make('created_at')->label('Dikirim')->dateTime('j M Y H:i'),
            TextEntry::make('handled_at')->label('Ditangani')->dateTime('j M Y H:i')->placeholder('Belum'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Dikirim')->dateTime('j M Y H:i')->sortable(),
                TextColumn::make('business_name')->label('Nama usaha')->searchable()->weight('medium'),
                TextColumn::make('contact_name')->label('Kontak')->searchable(),
                TextColumn::make('wa')->label('WhatsApp')->searchable()->copyable(),
                TextColumn::make('category')->label('Kategori')->formatStateUsing(fn ($state) => self::categoryName($state))->badge(),
                TextColumn::make('area')->formatStateUsing(fn ($state) => self::areaName($state))->toggleable(),
                IconColumn::make('handled')->label('Ditangani')->boolean()->state(fn (VendorApplication $record) => $record->handled_at !== null),
            ])
            ->filters([
                TernaryFilter::make('handled')
                    ->label('Ditangani')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('handled_at'),
                        false: fn (Builder $query) => $query->whereNull('handled_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('whatsapp')->label('WhatsApp')->icon(Heroicon::OutlinedChatBubbleLeftRight)->color('gray')
                    ->url(fn (VendorApplication $record) => self::waUrl($record->wa))->openUrlInNewTab(),
                Action::make('toggleHandled')
                    ->label(fn (VendorApplication $record) => $record->handled_at ? 'Buka lagi' : 'Tandai ditangani')
                    ->icon(fn (VendorApplication $record) => $record->handled_at ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheck)
                    ->action(fn (VendorApplication $record) => $record->update(['handled_at' => $record->handled_at ? null : now()])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVendorApplications::route('/')];
    }
}
