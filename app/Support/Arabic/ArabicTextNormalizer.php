<?php

namespace App\Support\Arabic;

/**
 * Arabic Text Normalizer
 * 
 * Provides utilities for normalizing Arabic text for search purposes.
 * Handles diacritics (tashkeel) removal, tatweel removal, character normalization, 
 * and text cleaning.
 * 
 * USE CASES:
 * - Searching for "بقرة" should match "بَقَرَةً" (with tashkeel)
 * - Searching for "الله" should match "اللَّه" or "ٱللَّه"
 * - Tatweel (ـ) is removed for better matching
 * 
 * CHARACTER NORMALIZATION:
 * - أ/إ/آ/ٱ → ا (Alef variants to plain Alef)
 * - ى → ي (Alef Maksura to Yaa)
 * - ة → ه (Taa Marbuta to Haa) - for better search matching
 * - ؤ → و (Waw with Hamza to Waw)
 * - ئ → ي (Yaa with Hamza to Yaa)
 */
class ArabicTextNormalizer
{
    /**
     * Arabic diacritics (tashkeel) Unicode characters to remove.
     * 
     * Unicode ranges covered:
     * - 064B-065F: Basic Arabic diacritics (harakat)
     * - 0670: Superscript Alef
     * - 06D6-06ED: Extended Quranic annotation marks
     */
    private const DIACRITICS = [
        // Basic Harakat (064B-065F)
        "\u{064B}", // Fathatan ً
        "\u{064C}", // Dammatan ٌ
        "\u{064D}", // Kasratan ٍ
        "\u{064E}", // Fatha َ
        "\u{064F}", // Damma ُ
        "\u{0650}", // Kasra ِ
        "\u{0651}", // Shadda ّ
        "\u{0652}", // Sukun ْ
        "\u{0653}", // Maddah Above ٓ
        "\u{0654}", // Hamza Above ٔ
        "\u{0655}", // Hamza Below ٕ
        "\u{0656}", // Subscript Alef ٖ
        "\u{0657}", // Inverted Damma ٗ
        "\u{0658}", // Mark Noon Ghunna ٘
        "\u{0659}", // Zwarakay ٙ
        "\u{065A}", // Vowel Sign Small V Above ٚ
        "\u{065B}", // Vowel Sign Inverted Small V Above ٛ
        "\u{065C}", // Vowel Sign Dot Below ٜ
        "\u{065D}", // Reversed Damma ٝ
        "\u{065E}", // Fatha with Two Dots ٞ
        "\u{065F}", // Wavy Hamza Below ٟ
        
        // Superscript Alef (0670)
        "\u{0670}", // Superscript Alef ٰ
        
        // Extended Quranic marks (06D6-06ED)
        "\u{06D6}", // Small High Ligature Sad with Lam with Alef Maksura
        "\u{06D7}", // Small High Ligature Qaf with Lam with Alef Maksura
        "\u{06D8}", // Small High Meem Initial Form
        "\u{06D9}", // Small High Lam Alef
        "\u{06DA}", // Small High Jeem
        "\u{06DB}", // Small High Three Dots
        "\u{06DC}", // Small High Seen
        "\u{06DD}", // End of Ayah ۝
        "\u{06DE}", // Start of Rub El Hizb ۞
        "\u{06DF}", // Small High Rounded Zero ۟
        "\u{06E0}", // Small High Upright Rectangular Zero ۠
        "\u{06E1}", // Small High Dotless Head of Khah ۡ
        "\u{06E2}", // Small High Meem Isolated Form ۢ
        "\u{06E3}", // Small Low Seen ۣ
        "\u{06E4}", // Small High Madda ۤ
        "\u{06E5}", // Small Waw ۥ
        "\u{06E6}", // Small Yeh ۦ
        "\u{06E7}", // Small High Yeh ۧ
        "\u{06E8}", // Small High Noon ۨ
        "\u{06E9}", // Place of Sajdah ۩
        "\u{06EA}", // Empty Centre Low Stop ۪
        "\u{06EB}", // Empty Centre High Stop ۫
        "\u{06EC}", // Rounded High Stop with Filled Centre ۬
        "\u{06ED}", // Small Low Meem ۭ
    ];

    /**
     * Tatweel (kashida) - Arabic text elongation character.
     * Unicode: 0640
     */
    private const TATWEEL = "\u{0640}"; // ـ

    /**
     * Character normalization map (variant -> normalized).
     * 
     * Normalizes different written forms of Arabic letters to a single form
     * for consistent search matching.
     */
    private const NORMALIZATION_MAP = [
        // Alef variants -> plain Alef (ا)
        'أ' => 'ا', // Alef with Hamza Above
        'إ' => 'ا', // Alef with Hamza Below
        'آ' => 'ا', // Alef with Madda Above
        'ٱ' => 'ا', // Alef Wasla
        'ٲ' => 'ا', // Alef with Wavy Hamza Above
        'ٳ' => 'ا', // Alef with Wavy Hamza Below
        
        // Yaa variants -> Yaa (ي)
        'ى' => 'ي', // Alef Maksura
        'ئ' => 'ي', // Yaa with Hamza Above
        'ۍ' => 'ي', // Yeh with Tail
        'ې' => 'ي', // E (Arabic letter)
        
        // Taa Marbuta -> Haa (for search matching)
        'ة' => 'ه',
        
        // Waw variants -> Waw (و)
        'ؤ' => 'و', // Waw with Hamza Above
        'ۇ' => 'و', // U
        'ۈ' => 'و', // Yu
        'ۉ' => 'و', // Kirghiz Yu
        
        // Hamza variants -> empty (remove standalone hamza for search)
        'ء' => '', // Standalone Hamza
    ];

    /**
     * Normalize Arabic text for search purposes.
     * 
     * This method:
     * 1. Removes all diacritics (tashkeel/harakat)
     * 2. Removes tatweel (kashida) characters
     * 3. Normalizes character variants (e.g., different Alef forms)
     * 4. Cleans up whitespace
     * 
     * @param string $text The Arabic text to normalize
     * @return string The normalized text ready for search
     */
    public static function normalize(string $text): string
    {
        // Remove all diacritics using array
        $text = str_replace(self::DIACRITICS, '', $text);
        
        // Remove tatweel
        $text = str_replace(self::TATWEEL, '', $text);
        
        // Normalize character variants
        $text = strtr($text, self::NORMALIZATION_MAP);
        
        // Clean up whitespace (multiple spaces to single, trim)
        $text = preg_replace('/\s+/u', ' ', $text);
        
        return trim($text);
    }

    /**
     * Remove only diacritics (harakat) without other normalization.
     * 
     * Use this when you want to keep character variants intact
     * but remove pronunciation marks.
     * 
     * @param string $text The Arabic text
     * @return string Text without diacritics
     */
    public static function removeDiacritics(string $text): string
    {
        $text = str_replace(self::DIACRITICS, '', $text);
        $text = str_replace(self::TATWEEL, '', $text);
        return $text;
    }

    /**
     * Prepare a search term for querying against normalized text.
     * 
     * Normalizes the search term so it can match stored normalized text.
     * 
     * @param string $term The search term (may include diacritics or not)
     * @return string The normalized search term
     */
    public static function prepareSearchTerm(string $term): string
    {
        return self::normalize($term);
    }

    /**
     * Create a SQL LIKE pattern for Arabic search.
     * 
     * @param string $term The search term
     * @return string The LIKE pattern with wildcards
     */
    public static function createLikePattern(string $term): string
    {
        $normalized = self::prepareSearchTerm($term);
        return '%' . $normalized . '%';
    }

    /**
     * Check if a string contains Arabic characters.
     * 
     * @param string $text The text to check
     * @return bool True if the text contains Arabic characters
     */
    public static function containsArabic(string $text): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }

    /**
     * Remove all non-Arabic characters from text.
     * Keeps Arabic letters, numbers, and basic punctuation.
     * 
     * @param string $text The text to clean
     * @return string Text with only Arabic content
     */
    public static function extractArabicOnly(string $text): string
    {
        // Keep Arabic Unicode range and spaces
        return preg_replace('/[^\x{0600}-\x{06FF}\s]/u', '', $text);
    }
}
