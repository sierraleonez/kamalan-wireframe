<?php

namespace App\Filament\Resources\Leads;

use App\Catalog\Catalog;
use App\Filament\Resources\Leads\Pages\ManageLeads;
use App\Models\Lead;
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

/** Kiriman form "Kasih tau kami": hanya dibaca dan ditandai sudah ditangani. */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengunjung';

    protected static ?string $modelLabel = 'permintaan';

    protected static ?string $pluralModelLabel = 'Permintaan';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $open = Lead::whereNull('handled_at')->count();

        return $open ? (string) $open : null;
    }

    public static function occasionName(?string $slug): string
    {
        return Catalog::type((string) $slug)['name'] ?? 'Lainnya';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('jenis')->label('Jenis acara')->formatStateUsing(fn ($state) => self::occasionName($state)),
            TextEntry::make('wa')->label('WhatsApp')->url(fn (Lead $record) => 'https://wa.me/'.preg_replace('/^0/', '62', ltrim($record->wa, '+')))->openUrlInNewTab()->copyable(),
            TextEntry::make('kategori')->placeholder('-'),
            TextEntry::make('area')->placeholder('-'),
            TextEntry::make('tamu')->label('Jumlah tamu')->placeholder('-'),
            TextEntry::make('budget')->placeholder('-'),
            TextEntry::make('tanggal')->placeholder('-'),
            TextEntry::make('created_at')->label('Dikirim')->dateTime('j M Y H:i'),
            TextEntry::make('cari')->label('Yang dicari')->placeholder('-')->columnSpanFull(),
            TextEntry::make('asal')->label('Dikirim dari halaman')->placeholder('-')->columnSpanFull(),
            TextEntry::make('handled_at')->label('Ditangani')->dateTime('j M Y H:i')->placeholder('Belum'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Dikirim')->dateTime('j M Y H:i')->sortable(),
                TextColumn::make('jenis')->label('Jenis acara')->formatStateUsing(fn ($state) => self::occasionName($state))->badge(),
                TextColumn::make('wa')->label('WhatsApp')->searchable()->copyable(),
                TextColumn::make('kategori')->placeholder('-'),
                TextColumn::make('area')->placeholder('-')->toggleable(),
                TextColumn::make('tamu')->placeholder('-')->toggleable(),
                TextColumn::make('budget')->placeholder('-')->toggleable(),
                TextColumn::make('cari')->label('Yang dicari')->limit(60)->wrap()->searchable()->placeholder('-'),
                IconColumn::make('handled')->label('Ditangani')->boolean()->state(fn (Lead $record) => $record->handled_at !== null),
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
                Action::make('toggleHandled')
                    ->label(fn (Lead $record) => $record->handled_at ? 'Buka lagi' : 'Tandai ditangani')
                    ->icon(fn (Lead $record) => $record->handled_at ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheck)
                    ->action(fn (Lead $record) => $record->update(['handled_at' => $record->handled_at ? null : now()])),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLeads::route('/')];
    }
}
