<?php

namespace App\Filament\Resources\ContentScopeResource\Pages;

use App\Filament\Resources\ContentScopeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContentScope extends EditRecord
{
    protected static string $resource = ContentScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
