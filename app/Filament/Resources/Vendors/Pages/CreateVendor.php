<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Catalog\VendorText;
use App\Filament\Resources\Vendors\VendorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    /** Vendor baru mulai dengan teks dari template, lalu dibuka di halaman edit. */
    protected function afterCreate(): void
    {
        VendorText::fill($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return VendorResource::getUrl('edit', ['record' => $this->record]);
    }
}
