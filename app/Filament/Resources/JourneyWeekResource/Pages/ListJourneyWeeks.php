<?php

namespace App\Filament\Resources\JourneyWeekResource\Pages;

use App\Filament\Resources\JourneyWeekResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJourneyWeeks extends ListRecords
{
    protected static string $resource = JourneyWeekResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
