<?php

namespace App\Filament\Concerns;

use App\Services\Categories\CategoryValidationService;
use App\Services\Categories\ScopeRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait SyncsCategories
 * 
 * Provides methods to sync categories for model records in Filament resource pages.
 */
trait SyncsCategories
{
    /**
     * Sync categories for a model record.
     * 
     * @param Model $record The model record
     * @param array $categoryIds Array of category IDs
     * @param string $modelClass Model class name
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function syncCategories(Model $record, array $categoryIds, string $modelClass): void
    {
        $validationService = app(CategoryValidationService::class);
        $scopeRegistry = app(ScopeRegistry::class);
        
        // Validate categories belong to the correct scope
        $validationService->validateCategoriesForModel($categoryIds, $modelClass);
        
        // Get the scope to determine the model class for the pivot
        $scope = $scopeRegistry->scopeForModel($modelClass);
        if (!$scope || !$scope->model_class) {
            return; // No scope configured, skip syncing
        }
        
        // Sync the polymorphic relationship
        $record->categories()->sync($categoryIds);
    }

    /**
     * Load category IDs for a record.
     * 
     * @param Model $record The model record
     * @return array Array of category IDs
     */
    protected function loadCategoryIds(Model $record): array
    {
        return $record->categories()->pluck('categories.id')->toArray();
    }
}
