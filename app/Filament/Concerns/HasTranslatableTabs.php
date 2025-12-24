<?php

namespace App\Filament\Concerns;

use App\Domain\Languages\Language;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;

trait HasTranslatableTabs
{
    /**
     * Create language tabs with translation fields.
     *
     * @param array $fields Fields configuration per language
     * @return Tabs
     */
    protected static function getTranslationTabs(callable $fieldsCallback): Tabs
    {
        $languages = Language::active()->orderBy('is_default', 'desc')->get();
        
        $tabs = [];
        
        foreach ($languages as $language) {
            $isRequired = $language->is_default;
            $isRtl = $language->direction === 'rtl';
            
            $tabs[] = Tab::make(strtoupper($language->code))
                ->label(strtoupper($language->code))
                ->schema($fieldsCallback($language->code, $isRequired))
                ->extraAttributes([
                    'dir' => $isRtl ? 'rtl' : 'ltr',
                    'class' => $isRtl ? 'rtl-content' : '',
                ]);
        }
        
        return Tabs::make('Translations')
            ->tabs($tabs)
            ->columnSpanFull()
            ->persistTabInQueryString();
    }
    
    /**
     * Fill translation fields from translation relationships.
     */
    protected function fillTranslations(): void
    {
        if (!$this->record) {
            return;
        }
        
        foreach ($this->record->translations as $translation) {
            $prefix = $translation->language_code . '_';
            
            foreach ($translation->getAttributes() as $key => $value) {
                if (!in_array($key, ['id', 'language_code', 'created_at', 'updated_at', $this->record->getForeignKey()])) {
                    $this->data[$prefix . $key] = $value;
                }
            }
        }
    }
    
    /**
     * Save translations from form data.
     */
    protected function saveTranslations(array $data): void
    {
        if (!$this->record) {
            return;
        }
        
        $languages = Language::active()->pluck('code');
        
        foreach ($languages as $langCode) {
            $translationData = [];
            $prefix = $langCode . '_';
            
            foreach ($data as $key => $value) {
                if (str_starts_with($key, $prefix)) {
                    $fieldName = substr($key, strlen($prefix));
                    $translationData[$fieldName] = $value;
                }
            }
            
            if (!empty($translationData)) {
                $this->record->translations()->updateOrCreate(
                    ['language_code' => $langCode],
                    $translationData
                );
            }
        }
    }
}
