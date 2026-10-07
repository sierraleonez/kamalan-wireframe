<?php

namespace App\Filament\Resources\Vendors;

use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\Area;
use App\Models\Category;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'vendor';

    protected static ?string $pluralModelLabel = 'Vendor';

    protected static ?string $recordTitleAttribute = 'name';

    /** Tautan halaman publik vendor, di jenis acara pertamanya. */
    public static function publicUrl(Vendor $v): ?string
    {
        $occ = $v->occasions->first();

        return $occ ? url('/'.$occ->slug.'/'.$v->category->slug.'/'.$v->slug) : null;
    }

    private static function category(Get $get): ?Category
    {
        return $get('category_id') ? Category::find($get('category_id')) : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Dasar')->columns(2)->components([
                TextInput::make('name')->label('Nama')->required()->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                        if ($operation === 'create' && blank($get('slug'))) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')->required()->maxLength(120)->unique(ignoreRecord: true)->regex('/^[a-z0-9-]+$/')
                    ->helperText('Bagian URL. Mengubahnya memutus tautan lama.'),
                Select::make('category_id')->label('Kategori')->relationship('category', 'name')->required()->live()
                    ->disabledOn('edit')->helperText(fn (string $operation) => $operation === 'edit' ? 'Kategori menentukan kode referral, jadi tidak bisa diganti.' : null),
                Select::make('area_id')->label('Area')->relationship('area', 'name', fn (Builder $query) => $query->orderBy('sort'))->required()->live(),
                TextInput::make('hood')->label('Kawasan')->required()->maxLength(60)
                    ->datalist(fn (Get $get) => $get('area_id') ? (Area::find($get('area_id'))?->hoods ?? []) : []),
                CheckboxList::make('occasions')->label('Jenis acara')->required()->columns(3)->columnSpanFull()
                    ->relationship('occasions', 'name', fn (Builder $query, Get $get) => $query->reorder()->orderBy('occasions.sort')
                        ->whereHas('categoryLinks', fn ($l) => $l->where('category_id', $get('category_id') ?: 0)))
                    ->helperText('Hanya jenis acara yang menampilkan kategori ini. Yang pertama dipakai sebagai default tautan WhatsApp.'),
            ]),

            Section::make('Kapasitas dan harga')->columns(3)->components([
                TextInput::make('cap_min')->label('Kapasitas min.')->numeric()->minValue(1)
                    ->helperText(fn (Get $get) => self::category($get)?->slug === 'catering' ? 'Minimum pax' : 'Kosongkan untuk hiburan, dekorasi, dokumentasi, AV'),
                TextInput::make('cap_max')->label('Kapasitas maks.')->numeric()->minValue(1)->gte('cap_min')->requiredWith('cap_min'),
                TextInput::make('price')->label('Harga mulai')->numeric()->required()->minValue(0)
                    ->helperText(fn (Get $get) => self::category($get)?->priceUnitLabel()),
                Select::make('setting')->label('Suasana')->options(['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'both' => 'Indoor + outdoor'])
                    ->visible(fn (Get $get) => self::category($get)?->slug === 'venue')
                    ->required(fn (Get $get) => self::category($get)?->slug === 'venue'),
            ]),

            Section::make('Fasilitas')->columns(3)->components([
                TextInput::make('parkir')->label('Parkir mobil')->numeric()->default(0)->minValue(0),
                TextInput::make('bus')->label('Parkir bus')->numeric()->default(0)->minValue(0),
                Grid::make(3)->columnSpanFull()->components([
                    Toggle::make('rooftop')->label('Rooftop'),
                    Toggle::make('catering_bebas')->label('Catering bebas'),
                    Toggle::make('transit')->label('Ada ruang transit'),
                    Toggle::make('av')->label('Sound & lighting sendiri'),
                    Toggle::make('pkp')->label('PKP (faktur pajak)'),
                    Toggle::make('halal')->label('Sertifikat halal'),
                ]),
            ]),

            Section::make('Penempatan')->columns(3)->components([
                Toggle::make('promoted')->label('Dipromosikan')->helperText('Ditandai di kartu dan diutamakan di beranda.'),
                TextInput::make('rank')->label('Urutan editor')->numeric()->default(1)->minValue(0)
                    ->helperText('Makin kecil makin atas di listing.'),
                TextInput::make('photos')->label('Jumlah foto')->numeric()->default(0)->minValue(0),
            ]),

            Section::make('Kontak')->columns(3)->components([
                TextInput::make('wa')->label('WhatsApp')->required()->regex('/^62\d{8,13}$/')->helperText('Format 628…, tanpa + atau spasi.'),
                TextInput::make('ig')->label('Instagram')->dehydrateStateUsing(fn (?string $state) => (string) $state)->maxLength(60)->placeholder('@nama'),
                TextInput::make('web')->label('Situs web')->dehydrateStateUsing(fn (?string $state) => (string) $state)->maxLength(120),
                TextInput::make('ref')->label('Kode referral')->disabled()->dehydrated(false)->visibleOn('edit')
                    ->helperText('Dipakai di pesan WhatsApp. Tetap selamanya.'),
            ]),

            Section::make('Tulisan')->components([
                Textarea::make('writeup')->label('Deskripsi dari tim')->rows(8)
                    ->helperText('Kosongkan untuk memakai deskripsi yang dibentuk dari data di atas. Pisahkan paragraf dengan baris kosong.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'area', 'occasions']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->badge(),
                TextColumn::make('area.name')->label('Area')->sortable(),
                TextColumn::make('price')->label('Harga')->sortable()->numeric(),
                TextColumn::make('rank')->label('Urutan')->sortable()->toggleable(),
                IconColumn::make('promoted')->label('Promo')->boolean(),
                TextColumn::make('ref')->searchable()->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->relationship('category', 'name'),
                SelectFilter::make('area')->label('Area')->relationship('area', 'name'),
                SelectFilter::make('occasions')->label('Jenis acara')->relationship('occasions', 'name'),
                TernaryFilter::make('promoted')->label('Dipromosikan'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('site')->label('Lihat')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (Vendor $record) => self::publicUrl($record))->openUrlInNewTab(),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'ref'];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
