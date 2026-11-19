<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Mail\ReviewRejectedMail;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\ImageColumn;
use App\Models\ReviewPhoto;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

/** Antrean ulasan pengunjung: Tampilkan atau Tolak (alasan dikirim ke penulis). */
class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $slug = 'ulasan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengunjung';

    protected static ?string $modelLabel = 'ulasan';

    protected static ?string $pluralModelLabel = 'Ulasan';

    public const STATUS = [Review::PENDING => 'Menunggu', Review::APPROVED => 'Tampil', Review::REJECTED => 'Ditolak'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $n = Review::where('status', Review::PENDING)->count();

        return $n ? (string) $n : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['vendor:id,name,slug', 'user:id,name,email,created_at', 'occasion:id,name', 'photos']))
            ->columns([
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => self::STATUS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { Review::PENDING => 'warning', Review::APPROVED => 'success', default => 'gray' }),
                TextColumn::make('vendor.name')->label('Vendor')->searchable(),
                TextColumn::make('rating')->label('★')->sortable(),
                TextColumn::make('body')->label('Ulasan')->wrap()->limit(220)
                    ->description(fn (Review $r) => implode(' · ', array_filter([$r->occasion?->name, $r->event_month?->locale('id')->translatedFormat('M Y')]))),
                ImageColumn::make('photo_thumbs')->label('Foto')
                    ->state(fn (Review $r) => $r->photos->map(fn (ReviewPhoto $p) => $p->url('thumb'))->all())
                    ->imageHeight(72)->imageWidth(72)->square()->wrap()->limit(5),
                TextColumn::make('user.name')->label('Penulis')
                    ->formatStateUsing(fn ($state, Review $r) => Review::displayName($r->user?->name))
                    ->description(fn (Review $r) => $r->user?->email.' · '.Review::where('user_id', $r->user_id)->count().' ulasan'),
                TextColumn::make('reject_reason')->label('Alasan ditolak')->wrap()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dikirim')->dateTime('j M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::STATUS)->default(Review::PENDING),
            ])
            ->recordActions([
                Action::make('approve')->label('Tampilkan')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Review $r) => $r->status !== Review::APPROVED)
                    ->action(function (Review $r) {
                        $r->update(['status' => Review::APPROVED, 'reject_reason' => null, 'reviewed_at' => now()]);
                        Notification::make()->title('Ulasan ditampilkan')->success()->send();
                    }),
                Action::make('reject')->label(fn (Review $r) => $r->status === Review::APPROVED ? 'Sembunyikan' : 'Tolak')
                    ->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (Review $r) => $r->status !== Review::REJECTED)
                    ->schema([
                        Textarea::make('reason')->label('Alasan (dikirim ke penulis)')->required()->maxLength(500)
                            ->default('Ulasan berisi nomor HP, link, atau promosi.')
                            ->helperText('Mis. bukan pengalaman nyata, berisi nomor HP/link/promosi, kata kasar, atau data pribadi orang lain.'),
                    ])
                    ->action(function (Review $r, array $data) {
                        $r->update(['status' => Review::REJECTED, 'reject_reason' => $data['reason'], 'reviewed_at' => now()]);
                        if ($r->user?->email) {
                            Mail::to($r->user->email)->send(new ReviewRejectedMail($r->vendor->name, $data['reason'], url('/ulasan/'.$r->vendor->slug)));
                        }
                        Notification::make()->title('Ulasan tidak ditampilkan; alasan dikirim ke penulis')->send();
                    }),
                Action::make('photos')->label('Hapus foto')->icon(Heroicon::OutlinedPhoto)->color('gray')
                    ->visible(fn (Review $r) => $r->photos->isNotEmpty())
                    ->modalDescription('Foto yang dicentang dihapus; ulasan dan foto lainnya tetap.')
                    ->schema(fn (Review $r) => [
                        CheckboxList::make('ids')->label('Foto')->required()->columns(5)
                            ->options($r->photos->mapWithKeys(fn (ReviewPhoto $p, $i) => [$p->id => 'Foto '.($i + 1)])->all())
                            ->descriptions($r->photos->mapWithKeys(fn (ReviewPhoto $p) => [$p->id => new \Illuminate\Support\HtmlString('<img src="'.e($p->url('thumb')).'" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:8px;margin-top:6px">')])->all()),
                    ])
                    ->action(function (Review $r, array $data) {
                        $r->photos()->whereIn('id', $data['ids'])->get()->each->delete();
                        Notification::make()->title(count($data['ids']).' foto dihapus')->success()->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReviews::route('/')];
    }
}
