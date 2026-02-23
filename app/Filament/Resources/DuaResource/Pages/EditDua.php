<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use App\Services\Categories\CategoryValidationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDua extends EditRecord
{
    protected static string $resource = DuaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate categories before saving
        if (isset($data['categories']) && is_array($data['categories'])) {
            $validationService = app(CategoryValidationService::class);
            $validationService->validateCategoriesForScopeKey($data['categories'], 'duas');
        }

        // Remove relationship fields from data
        unset($data['categories']);

        return $data;
    }

}
