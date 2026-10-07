<?php

namespace App\Filament\Resources\Bundles;

use App\Filament\Resources\Bundles\Pages\CreateBundle;
use App\Filament\Resources\Bundles\Pages\EditBundle;
use App\Filament\Resources\Bundles\Pages\ListBundles;
use App\Models\Bundle;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BundleResource extends Resource
{
    protected static ?string $model = Bundle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'bundle';

    protected static ?string $pluralModelLabel = 'Bundle';

    protected static ?string $recordTitleAttribute = 'title';

    public static function publicUrl(Bundle $b): string
    {
        return url('/'.$b->occasion->slug.'/bundle/'.$b->slug);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Paket')->columns(2)->components([
                TextInput::make('title')->label('Judul')->required()->maxLength(120)->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(120)->unique(ignoreRecord: true)->regex('/^[a-z0-9-]+$/')
                    ->helperText('Bagian URL. Mengubahnya memutus tautan lama.'),
                Select::make('occasion_id')->label('Jenis acara')->relationship('occasion', 'name', fn (Builder $query) => $query->orderBy('sort'))->required(),
                Select::make('area_id')->label('Area')->relationship('area', 'name', fn (Builder $query) => $query->orderBy('sort'))->required(),
                Select::make('eo_vendor_id')->label('EO penyusun')->searchable()->preload()
                    ->relationship('eo', 'name', fn (Builder $query) => $query->whereHas('category', fn ($c) => $c->where('slug', 'eo'))),
                Toggle::make('promoted')->label('Dipromosikan')->inline(false),
            ]),

            Section::make('Harga dan tamu')->columns(3)->components([
                Toggle::make('per_head')->label('Harga per peserta')->live()->columnSpanFull()
                    ->helperText('Aktif: harga dalam ribu rupiah per peserta. Mati: kisaran harga paket dalam juta rupiah.'),
                TextInput::make('price_min')->label(fn (Get $get) => $get('per_head') ? 'Harga per peserta (rb)' : 'Harga dari (jt)')->numeric()->required()->minValue(0),
                TextInput::make('price_max')->label('Harga sampai (jt)')->numeric()->gte('price_min')
                    ->visible(fn (Get $get) => ! $get('per_head'))->required(fn (Get $get) => ! $get('per_head')),
                TextInput::make('min_guests')->label('Minimum peserta')->numeric()->minValue(1)
                    ->visible(fn (Get $get) => (bool) $get('per_head'))->required(fn (Get $get) => (bool) $get('per_head')),
                TextInput::make('guests')->label('Jumlah tamu paket')->numeric()->required()->minValue(1),
                TextInput::make('validity')->label('Harga berlaku per')->required()->placeholder('September 2026'),
            ]),

            Section::make('Vendor di dalam paket')->components([
                Repeater::make('members')->hiddenLabel()->relationship()->orderColumn('sort')->reorderable()
                    ->columns(2)->addActionLabel('Tambah vendor')->minItems(1)
                    ->schema([
                        Select::make('category_id')->label('Kategori')->relationship('category', 'name')->required()->live(),
                        Select::make('vendor_id')->label('Vendor')->required()->searchable()
                            ->options(fn (Get $get) => $get('category_id') ? Vendor::where('category_id', $get('category_id'))->orderBy('name')->pluck('name', 'id') : []),
                    ]),
            ]),

            Section::make('Isi paket')->columns(2)->components([
                Repeater::make('included')->label('Termasuk')->simple(TextInput::make('item')->required()->maxLength(160))->reorderable()->addActionLabel('Tambah')->default([]),
                Repeater::make('excluded')->label('Belum termasuk')->simple(TextInput::make('item')->required()->maxLength(160))->reorderable()->addActionLabel('Tambah')->default([]),
            ]),

            Section::make('Kontak')->columns(2)->components([
                TextInput::make('wa')->label('WhatsApp')->required()->regex('/^62\d{8,13}$/')->helperText('Format 628…, tanpa + atau spasi.'),
                TextInput::make('ref')->label('Kode referral')->disabled()->dehydrated(false)->visibleOn('edit'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['occasion', 'area', 'eo']))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->wrap(),
                TextColumn::make('occasion.name')->label('Jenis acara')->badge(),
                TextColumn::make('area.name')->label('Area'),
                TextColumn::make('eo.name')->label('EO')->placeholder('-'),
                IconColumn::make('promoted')->label('Promo')->boolean(),
                TextColumn::make('ref'),
            ])
            ->filters([
                SelectFilter::make('occasion')->label('Jenis acara')->relationship('occasion', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('site')->label('Lihat')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Bundle $record) => self::publicUrl($record))->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBundles::route('/'),
            'create' => CreateBundle::route('/create'),
            'edit' => EditBundle::route('/{record}/edit'),
        ];
    }
}
