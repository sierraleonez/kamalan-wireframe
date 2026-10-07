<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Catalog\VendorText;
use App\Filament\Resources\Vendors\VendorResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

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

    /** Jenis acara baru dapat panel awal; rincian yang dikosongkan kembali ke default. */
    protected function afterSave(): void
    {
        VendorText::fill($this->record);
        $this->refreshFormData(self::TEXT_FIELDS);
    }
}
