<?php

namespace App\Filament\Resources\Domain\Datasets\DatasetVersionResource\Pages;

use App\Filament\Resources\Domain\Datasets\DatasetVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDatasetVersions extends ManageRecords
{
    protected static string $resource = DatasetVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
