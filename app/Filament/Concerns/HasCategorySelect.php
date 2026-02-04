<?php

namespace App\Filament\Concerns;

use App\Domain\Categories\Models\Category;
use App\Services\Categories\ScopeRegistry;
use Filament\Forms\Components\Select;

/**
 * Trait HasCategorySelect
 * 
 * Provides a reusable method to add category multi-select fields to Filament resources.
 */
trait HasCategorySelect
{
    /**
     * Get category select field for a model class.
     * 
     * @param string $modelClass Full model class name
     * @param string|null $scopeKey Optional scope key (if not provided, will be resolved from model class)
     * @return Select
     */
    protected static function getCategorySelectField(string $modelClass, ?string $scopeKey = null): Select
    {
        $scopeRegistry = app(ScopeRegistry::class);
        
        // Resolve scope
        $scope = $scopeKey 
            ? $scopeRegistry->scopeFor($scopeKey)
            : $scopeRegistry->scopeForModel($modelClass);
        
        $scopeId = $scope?->id;
        $scopeLabel = $scope?->label ?? 'Unknown';

        return Select::make('category_ids')
            ->label("Categories (Scope: {$scopeLabel})")
            ->multiple()
            ->searchable()
            ->preload()
            ->options(function () use ($scopeId) {
                if (!$scopeId) {
                    return [];
                }
                
                return Category::where('scope_id', $scopeId)
                    ->with('translations')
                    ->get()
                    ->mapWithKeys(function ($category) {
                        $name = $category->getName() ?? "Category #{$category->id}";
                        return [$category->id => $name];
                    });
            })
            ->getOptionLabelsUsing(function (array $values) {
                if (empty($values)) {
                    return [];
                }
                
                return Category::whereIn('id', $values)
                    ->with('translations')
                    ->get()
                    ->mapWithKeys(function ($category) {
                        $name = $category->getName() ?? "Category #{$category->id}";
                        return [$category->id => $name];
                    });
            })
            ->helperText("Select categories for this item. Only categories from the '{$scopeLabel}' scope are available.")
            ->placeholder('Select categories...')
            ->columnSpanFull();
    }
}
