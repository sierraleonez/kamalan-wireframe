<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Catalog\VendorText;
use App\Filament\Resources\Vendors\VendorResource;
use App\Support\VendorPhotos;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use RuntimeException;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    private const TEXT_FIELDS = ['specs', 'writeup', 'price_note', 'included', 'faqs', 'occasion_notes'];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site')->label('Lihat di situs')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => VendorResource::publicUrl($this->record))->openUrlInNewTab(),
            Action::make('regenerate')->label('Tulis ulang dari rincian')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Tulis ulang teks halaman?')
                ->modalDescription('Deskripsi, catatan harga, yang termasuk, tanya jawab, dan panel per jenis acara ditulis ulang dari rincian yang tersimpan. Editan teks sebelumnya hilang. Simpan perubahan rincian dulu.')
                ->action(function () {
                    VendorText::regenerate($this->record);
                    $this->refreshFormData(self::TEXT_FIELDS);
                    Notification::make()->title('Teks ditulis ulang')->success()->send();
                }),
            DeleteAction::make()->modalDescription('Vendor juga dikeluarkan dari bundle dan koleksi yang memuatnya.'),
        ];
    }

    /** Foto baru dari form (unggahan + link), diproses setelah vendor tersimpan. */
    private array $pendingUploads = [];

    private array $pendingUrls = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingUploads = array_values(array_filter((array) ($data['new_photos'] ?? [])));
        $this->pendingUrls = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['new_photo_urls'] ?? '')))));
        unset($data['new_photos'], $data['new_photo_urls']);

        return $data;
    }

    /** Jenis acara baru dapat panel awal; rincian yang dikosongkan kembali ke default. Lalu foto baru. */
    protected function afterSave(): void
    {
        VendorText::fill($this->record);
        $this->addPendingPhotos();
        // Isi ulang seluruh form dari database: teks yang baru diisi dan daftar foto (relasi) ikut tampil,
        // kolom unggahan dan link kosong lagi.
        $this->record->refresh();
        $this->fillForm();
    }

    private function addPendingPhotos(): void
    {
        $added = 0;
        $errors = [];
        foreach ($this->pendingUploads as $path) {
            try {
                VendorPhotos::addUpload($this->record, $path);
                $added++;
            } catch (RuntimeException $e) {
                $errors[] = basename($path).': '.$e->getMessage();
            }
        }
        foreach ($this->pendingUrls as $url) {
            try {
                VendorPhotos::addUrl($this->record, $url);
                $added++;
            } catch (RuntimeException $e) {
                $errors[] = $url.': '.$e->getMessage();
            }
        }
        $this->pendingUploads = $this->pendingUrls = [];

        if ($added) {
            Notification::make()->title($added.' foto ditambahkan')->success()->send();
        }
        if ($errors) {
            Notification::make()->title('Sebagian foto tidak tersimpan')->body(implode("\n", $errors))->danger()->persistent()->send();
        }
    }
}
