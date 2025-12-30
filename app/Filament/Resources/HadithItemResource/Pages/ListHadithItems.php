<?php

namespace App\Filament\Resources\HadithItemResource\Pages;

use App\Filament\Resources\HadithItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHadithItems extends ListRecords
{
    protected static string $resource = HadithItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
