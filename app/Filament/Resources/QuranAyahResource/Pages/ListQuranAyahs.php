<?php

namespace App\Filament\Resources\QuranAyahResource\Pages;

use App\Filament\Resources\QuranAyahResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuranAyahs extends ListRecords
{
    protected static string $resource = QuranAyahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
