<?php

namespace App\Filament\Resources\QuranAyahResource\Pages;

use App\Filament\Resources\QuranAyahResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuranAyah extends EditRecord
{
    protected static string $resource = QuranAyahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => config('app.content_admin_allow_delete', false)),
        ];
    }
}
