<?php

namespace App\Domain\QuranAllLang\Helpers;

use App\Domain\QuranAllLang\Models\QuranVerse;
use Illuminate\Support\Str;

/**
 * Reusable formatter for Quran verse display labels in Filament and elsewhere.
 * - Admin/select: "(Arabic surah name: ayah_number) ayah Arabic text"
 * - Locale-based: "{surah_name} • {ayah_number}" (e.g. "Al-Imran • 52" or "آل عمران • 52").
 */
class QuranVerseLabel
{
    /**
     * Format for Filament admin select/chips: "(Arabic surah name: ayah_number) ayah Arabic text".
     * Always uses Arabic surah name. Fallback: "(surah:ayah) text" if surah name missing.
     *
     * @param int $surahNumber Surah number (1-114)
     * @param int $ayahNumber Ayah number within the surah
     * @param string $ayahArabicText The verse Arabic text (optional; can be empty)
     * @param int|null $maxTextLength Truncate text to this length for display (null = no truncation)
     * @return string e.g. "(آل عمران: 52) إِنَّ مَثَلَ عِيسَىٰ عِندَ اللَّهِ..."
     */
    public static function formatForAdmin(
        int $surahNumber,
        int $ayahNumber,
        string $ayahArabicText = '',
        ?int $maxTextLength = 120
    ): string {
        $surahNameAr = SurahHelper::getArabicSurahName($surahNumber);
        $isGenericName = $surahNameAr === '' || preg_match('/^سورة\s*\d+$/u', $surahNameAr);

        if ($isGenericName) {
            $prefix = "({$surahNumber}:{$ayahNumber})";
        } else {
            $prefix = "({$surahNameAr}: {$ayahNumber})";
        }

        $text = trim($ayahArabicText);
        if ($maxTextLength !== null && $maxTextLength > 0 && mb_strlen($text) > $maxTextLength) {
            $text = Str::limit($text, $maxTextLength, '…');
        }

        return $text !== '' ? $prefix . ' ' . $text : $prefix;
    }

    /**
     * Format a verse as "Surah Name • Ayah Number" for the given locale.
     *
     * @param int $surahNumber Surah number (1-114)
     * @param int $ayahNumber Ayah number within the surah
     * @param string|null $locale Preferred locale: 'ar' for Arabic surah name, anything else for English. Null = use app locale
     * @return string e.g. "Al-Imran • 52" or "آل عمران • 52", or "(3:52)" if name missing
     */
    public static function format(int $surahNumber, int $ayahNumber, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $useArabic = ($locale === 'ar');

        $surahName = $useArabic
            ? SurahHelper::getArabicSurahName($surahNumber)
            : SurahHelper::getName($surahNumber);

        // Fallback if name is missing or is the generic "Surah N" / "سورة N"
        if ($surahName === '' || preg_match('/^Surah\s+\d+$/i', $surahName) || preg_match('/^سورة\s*\d+$/u', $surahName)) {
            return "({$surahNumber}:{$ayahNumber})";
        }

        return $surahName . ' • ' . $ayahNumber;
    }

    /**
     * Format a QuranVerse model instance (e.g. for getOptionLabelFromRecordUsing).
     * Uses app locale when $locale is null.
     */
    public static function forVerse(QuranVerse $verse, ?string $locale = null): string
    {
        return self::format(
            (int) $verse->surah_number,
            (int) $verse->ayah_number,
            $locale
        );
    }

    /**
     * Format for admin/select when you have a verse and its Arabic text.
     * Use this or formatForAdmin() directly when building option labels.
     */
    public static function forAdminWithText(QuranVerse $verse, string $ayahArabicText = '', ?int $maxTextLength = 120): string
    {
        return self::formatForAdmin(
            (int) $verse->surah_number,
            (int) $verse->ayah_number,
            $ayahArabicText,
            $maxTextLength
        );
    }
}
