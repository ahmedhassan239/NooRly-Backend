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
     * Mutate form data before creating the record.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extract translation data
        $this->translationData = $this->extractTranslationData($data);
        
        // Return base model data (scope_id, icon_key, icon_color are fillable)
        return [
            'scope_id' => $data['scope_id'] ?? null,
            'icon_key' => $data['icon_key'] ?? null,
            'icon_color' => $data['icon_color'] ?? null,
        ];
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
}
