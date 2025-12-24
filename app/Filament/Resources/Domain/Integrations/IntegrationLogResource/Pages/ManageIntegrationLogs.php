<?php

namespace App\Filament\Resources\Domain\Integrations\IntegrationLogResource\Pages;

use App\Filament\Resources\Domain\Integrations\IntegrationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageIntegrationLogs extends ManageRecords
{
    protected static string $resource = IntegrationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
