<?php

namespace App\Filament\Concerns;

use App\Domain\Categories\Models\Category;
use App\Services\Categories\ScopeRegistry;
use Filament\Forms\Components\Select;

/**
 * Trait HasScopeFilteredCategories
 * 
 * Provides a method to create scope-filtered category select fields.
 */
trait HasScopeFilteredCategories
{
    /**
     * Get category select field filtered by scope key.
     * 
     * @param string $scopeKey Scope key (e.g., 'lessons', 'duas', 'daily_tasks')
     * @return Select
     */
    protected static function getCategorySelectField(string $scopeKey): Select
    {
        $scopeRegistry = app(ScopeRegistry::class);
        $scopeId = $scopeRegistry->idFor($scopeKey);
        $scope = $scopeRegistry->scopeFor($scopeKey);
        $scopeLabel = $scope?->label ?? ucfirst($scopeKey);

        return Select::make('categories')
            ->label('Categories')
            ->relationship(
                name: 'categories',
                titleAttribute: 'name',
                modifyQueryUsing: function ($query) use ($scopeId) {
                    if ($scopeId) {
                        return $query->where('scope_id', $scopeId)
                            ->with('translations');
                    }
                    // If scope not found, return empty query
                    return $query->whereRaw('1 = 0');
                }
            )
            ->getOptionLabelFromRecordUsing(function (Category $record) {
                return $record->getName() ?? "Category #{$record->id}";
            })
            ->multiple()
            ->searchable()
            ->preload()
            ->placeholder('Select categories...')
            ->helperText(function () use ($scopeLabel, $scopeId) {
                if (!$scopeId) {
                    return "No categories available for this scope.";
                }
                return "Select categories for this item. Only categories from the '{$scopeLabel}' scope are available.";
            })
            ->columnSpanFull();
    }
}
