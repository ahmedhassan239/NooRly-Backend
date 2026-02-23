<?php

namespace App\Filament\Resources\VerseCollectionResource\Pages;

use App\Filament\Resources\VerseCollectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVerseCollections extends ListRecords
{
    protected static string $resource = VerseCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
