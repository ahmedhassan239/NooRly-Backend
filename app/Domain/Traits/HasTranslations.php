<?php

namespace App\Domain\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for models with translations.
 * Provides COALESCE-based query scopes for language fallback.
 */
trait HasTranslations
{
    /**
     * Get the translation table name.
     */
    abstract protected function getTranslationTable(): string;

    /**
     * Get the foreign key name for translations.
     */
    abstract protected function getTranslationForeignKey(): string;

    /**
     * Get translatable field names.
     */
    abstract protected function getTranslatableFields(): array;

    /**
     * Scope to include translations with fallback.
     *
     * @param Builder $query
     * @param string $lang Requested language code
     * @param string $fallbackLang Fallback language code (default: en)
     * @return Builder
     */
    public function scopeWithTranslation(Builder $query, string $lang = 'en', string $fallbackLang = 'en'): Builder
    {
        $table = $this->getTable();
        $translationTable = $this->getTranslationTable();
        $foreignKey = $this->getTranslationForeignKey();
        $fields = $this->getTranslatableFields();

        // Join requested language
        $query->leftJoin("{$translationTable} as t_req", function ($join) use ($table, $foreignKey, $lang) {
            $join->on("{$table}.id", '=', "t_req.{$foreignKey}")
                 ->where('t_req.language_code', '=', $lang);
        });

        // Join fallback language
        if ($lang !== $fallbackLang) {
            $query->leftJoin("{$translationTable} as t_en", function ($join) use ($table, $foreignKey, $fallbackLang) {
                $join->on("{$table}.id", '=', "t_en.{$foreignKey}")
                     ->where('t_en.language_code', '=', $fallbackLang);
            });
        }

        // Select base table columns
        $query->select("{$table}.*");

        // Add COALESCE for each translatable field
        foreach ($fields as $field) {
            if ($lang !== $fallbackLang) {
                $query->selectRaw("COALESCE(t_req.{$field}, t_en.{$field}) as {$field}");
            } else {
                $query->selectRaw("t_req.{$field} as {$field}");
            }
        }

        // Add resolved language
        if ($lang !== $fallbackLang) {
            $query->selectRaw("COALESCE(t_req.language_code, t_en.language_code) as resolved_lang");
        } else {
            $query->selectRaw("t_req.language_code as resolved_lang");
        }

        return $query;
    }

    /**
     * Scope to search in translated fields.
     */
    public function scopeSearchTranslated(Builder $query, string $searchTerm): Builder
    {
        $fields = $this->getTranslatableFields();
        
        return $query->where(function ($q) use ($searchTerm, $fields) {
            foreach ($fields as $field) {
                $q->orWhere("t_req.{$field}", 'LIKE', "%{$searchTerm}%")
                  ->orWhere("t_en.{$field}", 'LIKE', "%{$searchTerm}%");
            }
        });
    }
}
