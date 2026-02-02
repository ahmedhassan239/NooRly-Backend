<?php

namespace App\Observers;

use App\Domain\QuranAllLang\Models\VerseText;
use App\Support\Arabic\ArabicTextNormalizer;

/**
 * Observer for VerseText model.
 * 
 * Automatically normalizes Arabic text when a verse text is created or updated.
 * This ensures the text_normalized column is always in sync with the text column.
 */
class VerseTextObserver
{
    /**
     * Handle the VerseText "creating" event.
     * 
     * Normalizes the text before the record is created.
     */
    public function creating(VerseText $verseText): void
    {
        $this->normalizeText($verseText);
    }

    /**
     * Handle the VerseText "updating" event.
     * 
     * Re-normalizes the text if it has changed.
     */
    public function updating(VerseText $verseText): void
    {
        // Only re-normalize if the text column has changed
        if ($verseText->isDirty('text')) {
            $this->normalizeText($verseText);
        }
    }

    /**
     * Handle the VerseText "saving" event.
     * 
     * Alternative hook that fires on both create and update.
     * Using this as a fallback in case creating/updating doesn't work
     * with the external database connection.
     */
    public function saving(VerseText $verseText): void
    {
        // If text_normalized is not set and text exists, normalize it
        if (empty($verseText->text_normalized) && !empty($verseText->text)) {
            $this->normalizeText($verseText);
        }
    }

    /**
     * Normalize the text and set the text_normalized attribute.
     */
    private function normalizeText(VerseText $verseText): void
    {
        if (!empty($verseText->text)) {
            $verseText->text_normalized = ArabicTextNormalizer::normalize($verseText->text);
        }
    }
}
