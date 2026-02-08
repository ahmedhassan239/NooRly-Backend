<?php

namespace App\Filament\Forms\Components;

use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;

/**
 * ReligiousRichEditor Component
 * 
 * Extends TinyEditor with custom toolbar buttons for inserting
 * Quran Ayahs and Hadith Items references at cursor position.
 */
class ReligiousRichEditor extends TinyEditor
{
    protected string $view = 'filament.forms.components.religious-rich-editor';

    /**
     * Get the toolbar configuration with custom buttons.
     */
    public function getToolbar(): string
    {
        $baseToolbar = parent::getToolbar();
        
        // Add custom buttons after link button
        if (strpos($baseToolbar, 'link') !== false) {
            return str_replace('link', 'link | insertayah inserthadith', $baseToolbar);
        }
        
        // If no link button, append at the end
        return $baseToolbar . ' | insertayah inserthadith';
    }


}
