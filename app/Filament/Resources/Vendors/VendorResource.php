<?php

namespace App\Filament\Resources\Vendors;

use App\Catalog\Specs;
use App\Filament\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\Area;
use App\Models\Category;
use App\Models\Occasion;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
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

    /** Kategori yang punya kapasitas (min/maks). */
    private const WITH_CAPACITY = ['venue', 'catering', 'eo'];

    private static function cat(Get $get): ?string
    {
        return self::category($get)?->slug;
    }

    /** Isian rincian dari Specs: satu per kunci, tampil kalau kunci itu dipakai kategori terpilih. */
    private static function specInputs(): array
    {
        $keys = [];
        foreach (Category::pluck('slug') as $slug) {
            $keys += Specs::fields($slug);
        }
        $keys += array_splice($keys, array_search('dp_percent', array_keys($keys), true), 1); // DP paling akhir

        return array_map(fn (string $key) => TextInput::make('specs.'.$key)
            ->numeric()->minValue(0)
            ->label(fn (Get $get) => Specs::fields(self::cat($get) ?? '')[$key]['label'] ?? $key)
            ->suffix(fn (Get $get) => Specs::fields(self::cat($get) ?? '')[$key]['unit'] ?? null)
            ->visible(fn (Get $get) => isset(Specs::fields(self::cat($get) ?? '')[$key])
                && ! ($key === 'corkage' && $get('catering_bebas')))
            ->helperText(match ($key) {
                'overtime' => 'Kosongkan untuk 8% dari harga sewa.',
                'setup_hours', 'delivery_days' => 'Opsional; tampil di fakta kalau diisi.',
                default => null,
            }), array_keys($keys));
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

            Section::make('Foto')
                ->description('Foto langsung masuk begitu selesai diunggah. Foto pertama jadi sampul di kartu vendor; seret untuk mengubah urutan. Urutan, keterangan dan penghapusan berlaku setelah Simpan.')
                ->visibleOn('edit')
                ->collapsible()
                ->components([
                    Grid::make(['default' => 1, 'lg' => 2])->components([
                        FileUpload::make('new_photos')
                            ->label('Tambah foto')
                            ->multiple()
                            ->image()
                            ->storeFiles(false)
                            ->maxSize(15 * 1024)
                            ->maxFiles(config('media.vendor_max_photos'))
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                            ->helperText('Klik atau seret beberapa foto sekaligus (JPG, PNG, WebP; maks. 15 MB per foto).')
                            ->live()
                            // Raw state memakai kunci yang sama dengan serverId FilePond (state biasa sudah dinomori ulang).
                            ->afterStateUpdated(fn (FileUpload $component, $livewire) => $livewire->addPhotoFiles((array) $component->getRawState()))
                            // Server mengirim kunci file yang sudah diproses; hanya file itu yang dibuang dari kotak unggah
                            // (diulang sebentar kalau FilePond belum menandainya selesai). File yang masih diunggah dibiarkan.
                            ->extraAlpineAttributes(['x-on:vendor-photos-processed.window' => 'const keys = $event.detail.keys || []; const clean = () => pond && pond.getFiles().filter((f) => keys.includes(f.serverId) && f.status === 5).forEach((f) => pond.removeFile(f.id)); clean(); setTimeout(clean, 500); setTimeout(clean, 1500)']),
                        Group::make([
                            Textarea::make('new_photo_urls')
                                ->label('Atau tempel link gambar')
                                ->rows(3)
                                ->placeholder("https://…\nhttps://…")
                                ->helperText('Satu link per baris.'),
                            Actions::make([
                                Action::make('addPhotoLinks')
                                    ->label('Tambah dari link')
                                    ->icon(Heroicon::OutlinedArrowDownTray)
                                    ->color('gray')
                                    ->action(function (Get $get, Set $set, $livewire) {
                                        $livewire->addPhotoLinks((string) $get('new_photo_urls'));
                                        $set('new_photo_urls', null);
                                    }),
                            ])->key('photo-link-actions'),
                        ]),
                    ]),
                    Repeater::make('vendorPhotos')
                        ->hiddenLabel()
                        ->relationship()
                        ->orderColumn('sort')
                        ->reorderable()
                        ->addable(false)
                        ->grid(['default' => 1, 'md' => 2, 'xl' => 4])
                        ->itemLabel(fn (array $state) => filled($state['caption'] ?? null) ? $state['caption'] : 'Foto')
                        ->extraItemActions([
                            Action::make('makeCover')
                                ->label('Jadikan sampul')
                                ->icon(Heroicon::OutlinedStar)
                                ->action(function (array $arguments, Repeater $component) {
                                    $items = $component->getState();
                                    $key = $arguments['item'];
                                    $component->state([$key => $items[$key]] + $items);
                                }),
                        ])
                        ->schema([
                            ViewField::make('preview')->view('filament.vendor-photo-preview')->dehydrated(false),
                            TextInput::make('caption')->label('Keterangan')->maxLength(160)->placeholder('mis. Ruang utama, setup round table'),
                        ]),
                ]),

            Section::make('Kapasitas dan harga')->columns(3)->visible(fn (Get $get) => filled($get('category_id')))->components([
                TextInput::make('cap_min')->numeric()->minValue(1)
                    ->label(fn (Get $get) => match (self::cat($get)) {
                        'catering' => 'Minimum pax', 'eo' => 'Skala acara dari', default => 'Kapasitas dari'
                    })
                    ->visible(fn (Get $get) => in_array(self::cat($get), self::WITH_CAPACITY, true))
                    ->required(fn (Get $get) => in_array(self::cat($get), self::WITH_CAPACITY, true)),
                TextInput::make('cap_max')->numeric()->minValue(1)->gte('cap_min')
                    ->label(fn (Get $get) => match (self::cat($get)) {
                        'catering' => 'Maksimum pax per sesi', 'eo' => 'Skala acara sampai', default => 'Kapasitas sampai'
                    })
                    ->visible(fn (Get $get) => in_array(self::cat($get), self::WITH_CAPACITY, true))
                    ->required(fn (Get $get) => in_array(self::cat($get), self::WITH_CAPACITY, true)),
                TextInput::make('price')->label('Harga mulai')->numeric()->required()->minValue(0)
                    ->helperText(fn (Get $get) => self::category($get)?->priceUnitLabel()),
            ]),

            Section::make('Rincian')
                ->heading(fn (Get $get) => 'Rincian '.(self::category($get)?->name ?? ''))
                ->description('Yang ditanyakan untuk kategori ini. Fakta di halaman vendor dibaca dari sini.')
                ->columns(3)
                ->visible(fn (Get $get) => filled($get('category_id')))
                ->components([
                    Select::make('setting')->label('Suasana')->options(['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'both' => 'Indoor + outdoor'])
                        ->visible(fn (Get $get) => self::cat($get) === 'venue')->required(fn (Get $get) => self::cat($get) === 'venue'),
                    ...self::specInputs(),
                    TextInput::make('parkir')->label('Parkir mobil')->numeric()->default(0)->minValue(0)
                        ->visible(fn (Get $get) => self::cat($get) === 'venue'),
                    TextInput::make('bus')->numeric()->default(0)->minValue(0)
                        ->label(fn (Get $get) => self::cat($get) === 'eo' ? 'Parkir bus di venue rekanan' : 'Parkir bus')
                        ->visible(fn (Get $get) => in_array(self::cat($get), ['venue', 'eo'], true)),
                    Grid::make(3)->columnSpanFull()->components([
                        Toggle::make('rooftop')->label('Rooftop')->visible(fn (Get $get) => self::cat($get) === 'venue'),
                        Toggle::make('transit')->label('Ada ruang transit')->visible(fn (Get $get) => self::cat($get) === 'venue'),
                        Toggle::make('catering_bebas')->label('Catering bebas')->live()->visible(fn (Get $get) => self::cat($get) === 'venue'),
                        Toggle::make('halal')->label('Sertifikat halal')->visible(fn (Get $get) => self::cat($get) === 'catering'),
                        Toggle::make('av')->label('Sound & lighting sendiri')->visible(fn (Get $get) => in_array(self::cat($get), ['venue', 'eo'], true)),
                        Toggle::make('pkp')->label('PKP (faktur pajak)'),
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

            Section::make('Teks halaman')
                ->description('Semua teks di halaman vendor. Diisi dari template saat vendor dibuat; setelah itu bebas diedit. "Tulis ulang dari rincian" di atas menimpa semuanya.')
                ->visibleOn('edit')
                ->collapsible()
                ->components([
                    Textarea::make('writeup')->label('Deskripsi')->rows(6)
                        ->helperText('Paragraf pertama jadi pembuka. Pisahkan paragraf dengan baris kosong.'),
                    TextInput::make('price_note')->label('Catatan harga')->maxLength(200)
                        ->helperText('Tampil di bawah harga: apa yang belum termasuk.'),
                    Repeater::make('occasion_notes')->label('Panel per jenis acara')
                        ->schema([
                            Select::make('occasion')->label('Jenis acara')->required()->distinct()
                                ->options(fn () => Occasion::orderBy('sort')->pluck('name', 'slug')),
                            TextInput::make('title')->label('Judul panel')->required()->maxLength(80),
                            Textarea::make('text')->label('Isi')->rows(3)->required()->columnSpanFull(),
                        ])
                        ->columns(2)->collapsible()->reorderable(false)->addActionLabel('Tambah panel')
                        ->itemLabel(fn (array $state) => Occasion::where('slug', $state['occasion'] ?? '')->value('name'))
                        ->helperText('Hanya panel untuk jenis acara yang dicentang di atas yang tampil.'),
                    Repeater::make('included')->label('Yang termasuk')
                        ->schema([
                            TextInput::make('label')->label('Poin')->required()->maxLength(120),
                            TextInput::make('note')->label('Keterangan')->maxLength(160),
                        ])
                        ->columns(2)->reorderable()->addActionLabel('Tambah poin'),
                    Repeater::make('faqs')->label('Tanya jawab')
                        ->schema([
                            TextInput::make('q')->label('Pertanyaan')->required()->maxLength(160),
                            Textarea::make('a')->label('Jawaban')->required()->rows(2),
                        ])
                        ->reorderable()->collapsible()->addActionLabel('Tambah pertanyaan')
                        ->itemLabel(fn (array $state) => $state['q'] ?? null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['category', 'area', 'occasions', 'vendorPhotos'])->withCount('vendorPhotos'))
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('cover')->label('Sampul')
                    ->disk(fn () => config('media.disk'))
                    ->state(fn (Vendor $record) => $record->vendorPhotos->first()?->file('card'))
                    ->width(72)->height(48)->extraImgAttributes(['style' => 'object-fit:cover;border-radius:6px'])
                    ->placeholder('Belum ada'),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Kategori')->badge(),
                TextColumn::make('area.name')->label('Area')->sortable(),
                TextColumn::make('price')->label('Harga')->sortable()->numeric(),
                TextColumn::make('rank')->label('Urutan')->sortable()->toggleable(),
                TextColumn::make('vendor_photos_count')->label('Foto')->sortable()->formatStateUsing(fn ($state) => $state ? $state.' foto' : '—'),
                IconColumn::make('promoted')->label('Promo')->boolean(),
                TextColumn::make('ref')->searchable()->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')->label('Kategori')->relationship('category', 'name'),
                SelectFilter::make('area')->label('Area')->relationship('area', 'name'),
                SelectFilter::make('occasions')->label('Jenis acara')->relationship('occasions', 'name'),
                TernaryFilter::make('promoted')->label('Dipromosikan'),
                TernaryFilter::make('has_photos')->label('Foto')
                    ->trueLabel('Ada foto')->falseLabel('Belum ada foto')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('vendorPhotos'),
                        false: fn (Builder $query) => $query->whereDoesntHave('vendorPhotos'),
                    ),
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
