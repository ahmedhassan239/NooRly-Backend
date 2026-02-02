<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Domain\Languages\Language;
use App\Filament\Resources\CategoryResource;
use App\Services\Categories\CategorySlugGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    /**
     * Translation data extracted from form.
     */
    private array $translationData = [];

    /**
     * Verse IDs from form.
     */
    private array $verseIds = [];

    /**
     * Hadith IDs from form.
     */
    private array $hadithIds = [];

    /**
     * Mutate form data before creating the record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract translation data
        $this->translationData = $this->extractTranslationData($data);
        
        // Extract relationship data
        $this->verseIds = $data['verse_ids'] ?? [];
        $this->hadithIds = $data['hadith_ids'] ?? [];
        
        // Return only base model data (categories table has no translatable columns now)
        return [];
    }

    /**
     * Extract translation data from form fields.
     */
    private function extractTranslationData(array $data): array
    {
        $languages = Language::active()->pluck('code')->toArray();
        $translationData = [];

        foreach ($languages as $langCode) {
            $prefix = $langCode . '_';
            $translation = [];

            foreach ($data as $key => $value) {
                if (str_starts_with($key, $prefix)) {
                    $field = substr($key, strlen($prefix));
                    
                    // Skip helper fields
                    if (in_array($field, ['customize_slug'])) {
                        continue;
                    }
                    
                    $translation[$field] = $value;
                }
            }

            // Only include if there's actual content
            if (!empty($translation['name'])) {
                // Ensure slug is generated if empty
                if (empty($translation['slug'])) {
                    $slugGenerator = app(CategorySlugGenerator::class);
                    $translation['slug'] = $slugGenerator->generate($translation['name'], $langCode);
                }
                
                $translationData[$langCode] = $translation;
            }
        }

        return $translationData;
    }

    /**
     * Handle post-creation tasks.
     */
    protected function afterCreate(): void
    {
        // Save translations
        $this->saveTranslations();
        
        // Sync verse relationships
        $this->syncVerses();
        
        // Sync hadith relationships
        $this->syncHadiths();
    }

    /**
     * Save translations to the database.
     */
    private function saveTranslations(): void
    {
        $slugGenerator = app(CategorySlugGenerator::class);

        foreach ($this->translationData as $langCode => $fields) {
            // Ensure slug is unique
            if (!$slugGenerator->isUnique($fields['slug'] ?? '', $langCode, $this->record->id)) {
                $fields['slug'] = $slugGenerator->generate($fields['name'], $langCode, $this->record->id);
            }

            $this->record->translations()->create([
                'language_code' => $langCode,
                ...$fields,
            ]);
        }
    }

    /**
     * Sync verse relationships (cross-database).
     */
    private function syncVerses(): void
    {
        $this->record->syncVerses($this->verseIds);
    }

    /**
     * Sync hadith relationships (cross-database).
     */
    private function syncHadiths(): void
    {
        $this->record->syncHadiths($this->hadithIds);
    }
}
