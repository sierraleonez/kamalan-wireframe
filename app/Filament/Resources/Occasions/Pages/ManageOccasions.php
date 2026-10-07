<?php

namespace App\Filament\Resources\Occasions\Pages;

use App\Filament\Resources\Occasions\OccasionResource;
use App\Models\Occasion;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOccasions extends ManageRecords
{
    protected static string $resource = OccasionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver()->mutateDataUsing(
                fn (array $data) => OccasionResource::withPhoto($data, null) + ['sort' => (int) Occasion::max('sort') + 1],
            ),
        ];
    }
}
