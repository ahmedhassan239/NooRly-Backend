<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

/**
 * Validation rule for unique translated slugs.
 * 
 * Ensures that a slug is unique within a specific language
 * in a translation table.
 */
class UniqueTranslatedSlug implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param string $table The translations table name
     * @param string $languageCode The language code
     * @param int|null $excludeId The ID to exclude (for updates)
     * @param string $foreignKey The foreign key column name
     */
    public function __construct(
        protected string $table,
        protected string $languageCode,
        protected ?int $excludeId = null,
        protected string $foreignKey = 'category_id'
    ) {}

    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $query = DB::table($this->table)
            ->where('language_code', $this->languageCode)
            ->where('slug', $value);

        if ($this->excludeId !== null) {
            $query->where($this->foreignKey, '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail("The slug '{$value}' is already taken for language '{$this->languageCode}'.");
        }
    }

    /**
     * Create a rule for category translations.
     */
    public static function forCategory(string $languageCode, ?int $excludeId = null): self
    {
        return new self(
            table: 'category_translations',
            languageCode: $languageCode,
            excludeId: $excludeId,
            foreignKey: 'category_id'
        );
    }
}
