<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use App\Services\Categories\CategoryValidationService;
use Filament\Resources\Pages\CreateRecord;

class CreateDua extends CreateRecord
{
    protected static string $resource = DuaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
