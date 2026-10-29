<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Catalog\VendorText;
use App\Filament\Resources\Vendors\VendorResource;
use App\Support\VendorPhotos;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
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

    /** Unggahan atau link yang belum sempat diproses (mis. Simpan ditekan sebelum unggahan selesai). */
    private array $pendingUploads = [];

    private string $pendingUrls = '';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingUploads = array_values(array_filter((array) ($data['new_photos'] ?? [])));
        $this->pendingUrls = (string) ($data['new_photo_urls'] ?? '');
        unset($data['new_photos'], $data['new_photo_urls']);

        return $data;
    }

    /** Jenis acara baru dapat panel awal; rincian yang dikosongkan kembali ke default. Lalu sisa foto baru. */
    protected function afterSave(): void
    {
        VendorText::fill($this->record);
        if ($this->pendingUploads) {
            $this->addPhotoFiles($this->pendingUploads, refresh: false);
        }
        if (trim($this->pendingUrls) !== '') {
            $this->addPhotoLinks($this->pendingUrls, refresh: false);
        }
        $this->pendingUploads = [];
        $this->pendingUrls = '';
        // Isi ulang seluruh form dari database: teks yang baru diisi dan daftar foto ikut tampil.
        $this->record->refresh();
        $this->fillForm();
    }

    /** Dipanggil begitu unggahan selesai: setiap file langsung jadi foto vendor. File sementara yang sudah
     *  diproses (dan dihapus) dilewati, kalau pembaruan yang sama datang dua kali. */
    public function addPhotoFiles(array $files, bool $refresh = true): void
    {
        $files = array_filter($files, fn ($f) => is_string($f) || ($f instanceof TemporaryUploadedFile && $f->exists()));
        $this->addPhotos(array_map(fn ($file) => [
            is_string($file) ? basename($file) : $file->getClientOriginalName(),
            function () use ($file) {
                if (is_string($file)) {   // path di disk public (unggahan lama)
                    return VendorPhotos::addUpload($this->record, $file);
                }
                try {
                    return VendorPhotos::addBytes($this->record, $file->get());
                } finally {
                    $file->delete();
                }
            },
        ], $files), $refresh);
        if ($files) {
            // Kunci state = serverId di FilePond: kotak unggah membuang file yang sudah diproses.
            $this->dispatch('vendor-photos-processed', keys: array_map('strval', array_keys($files)));
        }
    }

    /** Tombol "Tambah dari link": satu link per baris. */
    public function addPhotoLinks(string $text, bool $refresh = true): void
    {
        $urls = array_values(array_filter(array_map('trim', preg_split('/\R/', $text))));
        $this->addPhotos(array_map(fn ($url) => [$url, fn () => VendorPhotos::addUrl($this->record, $url)], $urls), $refresh);
    }

    /** @param  list<array{0:string, 1:callable}>  $jobs  [label, tambah] */
    private function addPhotos(array $jobs, bool $refresh): void
    {
        $added = 0;
        $errors = [];
        foreach ($jobs as [$label, $add]) {
            try {
                $add();
                $added++;
            } catch (RuntimeException $e) {
                $errors[] = $label.': '.$e->getMessage();
            }
        }

        if ($added) {
            Notification::make()->title($added.' foto ditambahkan')->success()->send();
            if ($refresh) {
                $this->appendNewPhotosToList();
            }
        }
        if ($errors) {
            Notification::make()->title('Sebagian foto tidak tersimpan')->body(implode("\n", $errors))->danger()->persistent()->send();
        }
    }

    /** Tambahkan foto baru ke daftar tanpa membuang urutan/keterangan yang belum disimpan. */
    private function appendNewPhotosToList(): void
    {
        $list = $this->form->getComponent(fn ($c) => $c instanceof Repeater && $c->getName() === 'vendorPhotos', withHidden: true);
        if (! $list) {
            return;
        }
        $current = (array) $list->getRawState();
        $list->clearCachedExistingRecords();
        $list->fillFromRelationship();
        $fresh = (array) $list->getRawState();
        $list->state($current + array_diff_key($fresh, $current));
    }
}
