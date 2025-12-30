<?php

namespace App\Filament\Resources\HadithItemResource\Pages;

use App\Filament\Resources\HadithItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHadithItem extends EditRecord
{
    protected static string $resource = HadithItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => config('app.content_admin_allow_delete', false)),
        ];
    }
}
