<?php

namespace App\Filament\Resources\ContentScopeResource\Pages;

use App\Filament\Resources\ContentScopeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContentScopes extends ListRecords
{
    protected static string $resource = ContentScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
