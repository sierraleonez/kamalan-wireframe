<?php

namespace App\Filament\Support;

use App\Support\ImageStore;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Js;
use RuntimeException;

/**
 * Isian foto yang sama di semua tempat: unggah (klik atau seret), tempel link, salin link, keterangan.
 * Kolom: {prefix}image (path di disk public), {prefix}image_url (sementara), {prefix}image_alt.
 */
final class PhotoField
{
    /** @return array<int, Component> */
    public static function make(string $prefix = '', bool $required = false, ?string $help = null): array
    {
        return [
            FileUpload::make($prefix.'image')
                ->label('Foto')
                ->image()
                ->disk(fn () => config('media.disk'))
                ->directory(ImageStore::DIR.'/uploads')
                ->maxSize(15 * 1024)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                ->imagePreviewHeight('160')
                ->required(fn (Get $get) => $required && blank($get($prefix.'image_url')))
                ->validationMessages(['required' => 'Unggah foto atau tempel link gambar.'])
                ->helperText($help ?? 'Klik atau seret foto ke sini (JPG, PNG, WebP; maks. 15 MB).')
                ->hintAction(
                    Action::make('copy')
                        ->label('Salin link')
                        ->icon(Heroicon::OutlinedClipboardDocument)
                        ->visible(fn ($state) => filled(self::path($state)))
                        ->alpineClickHandler(fn ($state) => 'window.navigator.clipboard.writeText('.Js::from(url(ImageStore::url(self::path($state)))).'); $tooltip('.Js::from('Link disalin').', { timeout: 1500 })'),
                ),
            TextInput::make($prefix.'image_url')
                ->label('Atau tempel link gambar')
                ->placeholder('https://…')
                ->url()
                ->dehydrated()
                ->helperText('Gambar diunduh dan disimpan di server saat disimpan, lalu menggantikan foto di atas.'),
            TextInput::make($prefix.'image_alt')
                ->label('Keterangan foto')
                ->maxLength(150)
                ->helperText('Untuk pembaca layar dan Google, mis. "Resepsi di taman saat sore".'),
        ];
    }

    /**
     * Proses isian sebelum disimpan: link → unduh, unggahan baru → WebP, foto lama dihapus kalau diganti.
     * Mengembalikan data dengan 'image' berisi path akhir dan 'image_url' dibuang.
     */
    public static function apply(array $data, string $name, int $maxWidth, ?string $old): array
    {
        $new = self::path($data['image'] ?? null);
        $url = trim((string) ($data['image_url'] ?? ''));

        try {
            if ($url !== '') {
                $new = ImageStore::fromUrl($url, $name, $maxWidth);
            } elseif ($new && $new !== $old) {
                $new = ImageStore::fromUpload($new, $name, $maxWidth);
            }
        } catch (RuntimeException $e) {
            Notification::make()->title('Foto tidak tersimpan')->body($e->getMessage())->danger()->send();

            throw new Halt;
        }

        if ($new !== $old) {
            ImageStore::delete($old);
        }
        unset($data['image_url']);
        $data['image'] = $new;
        $data['image_alt'] = filled($data['image_alt'] ?? null) ? trim($data['image_alt']) : null;

        return $data;
    }

    /** State FileUpload bisa string atau array [uuid => path]. */
    public static function path(mixed $state): ?string
    {
        return is_array($state) ? (array_values($state)[0] ?? null) : $state;
    }
}
