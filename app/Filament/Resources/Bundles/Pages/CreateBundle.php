<?php

namespace App\Filament\Resources\Bundles\Pages;

use App\Filament\Resources\Bundles\BundleResource;
use App\Models\Bundle;
use Filament\Resources\Pages\CreateRecord;

class CreateBundle extends CreateRecord
{
    protected static string $resource = BundleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['sort' => (int) Bundle::max('sort') + 1];
    }
}
