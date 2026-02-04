<?php

namespace App\Services\Categories;

use App\Domain\Categories\Models\Category;
use App\Domain\ContentScopes\ContentScope;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * CategoryValidationService
 * 
 * Validates that categories belong to the correct scope when attaching to models.
 */
class CategoryValidationService
{
    protected ScopeRegistry $scopeRegistry;

    public function __construct(ScopeRegistry $scopeRegistry)
    {
        $this->scopeRegistry = $scopeRegistry;
    }

    /**
     * Validate that category IDs belong to the specified scope.
     * 
     * @param array $categoryIds Array of category IDs
     * @param int $scopeId Expected scope ID
     * @throws ValidationException If validation fails
     */
    public function validateCategoriesForScope(array $categoryIds, int $scopeId): void
    {
        if (empty($categoryIds)) {
            return; // Empty is valid (no categories selected)
        }

        // Get all categories and check their scope_id
        $categories = Category::whereIn('id', $categoryIds)->get();
        
        $invalidCategories = $categories->filter(function ($category) use ($scopeId) {
            return $category->scope_id !== $scopeId;
        });

        if ($invalidCategories->isNotEmpty()) {
            $invalidNames = $invalidCategories->map(function ($category) {
                return $category->getName() ?? "Category #{$category->id}";
            })->implode(', ');

            throw ValidationException::withMessages([
                'category_ids' => "Selected categories do not match this module scope. Invalid categories: {$invalidNames}",
            ]);
        }
    }

    /**
     * Validate categories for a model class.
     * 
     * @param array $categoryIds Array of category IDs
     * @param string $modelClass Model class name
     * @throws ValidationException If validation fails
     */
    public function validateCategoriesForModel(array $categoryIds, string $modelClass): void
    {
        $scope = $this->scopeRegistry->scopeForModel($modelClass);
        
        if (!$scope) {
            throw ValidationException::withMessages([
                'category_ids' => "No active scope found for model: {$modelClass}",
            ]);
        }

        $this->validateCategoriesForScope($categoryIds, $scope->id);
    }

    /**
     * Validate categories for a scope key.
     * 
     * @param array $categoryIds Array of category IDs
     * @param string $scopeKey Scope key (e.g., 'lessons', 'duas')
     * @throws ValidationException If validation fails
     */
    public function validateCategoriesForScopeKey(array $categoryIds, string $scopeKey): void
    {
        $scopeId = $this->scopeRegistry->idFor($scopeKey);
        
        if (!$scopeId) {
            throw ValidationException::withMessages([
                'category_ids' => "Scope '{$scopeKey}' not found or inactive",
            ]);
        }

        $this->validateCategoriesForScope($categoryIds, $scopeId);
    }
}
