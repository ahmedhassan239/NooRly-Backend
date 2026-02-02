<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Domain\Languages\Language;
use App\Filament\Resources\CategoryResource;
use App\Services\Categories\CategorySlugGenerator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
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

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Mutate form data before filling the form.
     * Load translations into form fields.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load translations into form fields
        foreach ($this->record->translations as $translation) {
            $prefix = $translation->language_code . '_';
            
            $data[$prefix . 'name'] = $translation->name;
            $data[$prefix . 'slug'] = $translation->slug;
            $data[$prefix . 'description'] = $translation->description;
        }
        
        return $data;
    }

    /**
     * Mutate form data before saving the record.
     */
    protected function mutateFormDataBeforeSave(array $data): array
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
                    $translation['slug'] = $slugGenerator->generate($translation['name'], $langCode, $this->record->id);
                }
                
                $translationData[$langCode] = $translation;
            }
        }

        return $translationData;
    }

    /**
     * Handle post-save tasks.
     */
    protected function afterSave(): void
    {
        // Update translations
        $this->saveTranslations();
        
        // Sync verse relationships
        $this->syncVerses();
        
        // Sync hadith relationships
        $this->syncHadiths();
    }

    /**
     * Save/update translations in the database.
     */
    private function saveTranslations(): void
    {
        $slugGenerator = app(CategorySlugGenerator::class);

        foreach ($this->translationData as $langCode => $fields) {
            // Ensure slug is unique (excluding current record)
            if (!$slugGenerator->isUnique($fields['slug'] ?? '', $langCode, $this->record->id)) {
                $fields['slug'] = $slugGenerator->generate($fields['name'], $langCode, $this->record->id);
            }

            $this->record->translations()->updateOrCreate(
                ['language_code' => $langCode],
                $fields
            );
        }

        // Remove translations for languages that are no longer in the data
        $existingLanguages = array_keys($this->translationData);
        $this->record->translations()
            ->whereNotIn('language_code', $existingLanguages)
            ->delete();
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
