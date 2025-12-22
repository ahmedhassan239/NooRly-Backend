<?php

namespace App\Filament\Resources\DailyTaskResource\Pages;

use App\Filament\Resources\DailyTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyTasks extends ListRecords
{
    protected static string $resource = DailyTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
