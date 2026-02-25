<?php

namespace App\Rules;

use App\Domain\ContentScopes\ContentScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Validates that category_id exists in categories and belongs to the given scope.
 * Use for content create/update to ensure category is valid for this content type.
 *
 * Example:
 *   'category_id' => ['required', new CategoryExistsInScopeRule('adhkar')],
 */
class CategoryExistsInScopeRule implements ValidationRule
{
    public function __construct(
        protected string $scopeKey
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // let required rule handle presence
        }

        $scope = ContentScope::where('key', $this->scopeKey)->first();
        if (!$scope) {
            $fail("Scope '{$this->scopeKey}' is not configured.");
            return;
        }

        $exists = DB::table('categories')
            ->where('id', $value)
            ->where('scope_id', $scope->id)
            ->exists();

        if (!$exists) {
            $fail("The selected category is invalid or does not belong to scope '{$this->scopeKey}'.");
        }
    }
}
