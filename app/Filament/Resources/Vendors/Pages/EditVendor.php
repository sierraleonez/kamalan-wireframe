<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Resources\Vendors\VendorResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditVendor extends EditRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('site')->label('Lihat di situs')->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => VendorResource::publicUrl($this->record))->openUrlInNewTab(),
            DeleteAction::make()->modalDescription('Vendor juga dikeluarkan dari bundle dan koleksi yang memuatnya.'),
        ];
    }
}
