<?php

namespace App\Filament\Resources\AdhkarResource\Pages;

use App\Filament\Resources\AdhkarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdhkar extends EditRecord
{
    protected static string $resource = AdhkarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
