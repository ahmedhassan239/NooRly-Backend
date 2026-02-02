<?php

namespace Tests\Unit;

use App\Support\Arabic\ArabicTextNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArabicTextNormalizer.
 * 
 * Tests cover:
 * - Diacritics (tashkeel) removal
 * - Tatweel removal
 * - Character normalization (Alef variants, Yaa, Taa Marbuta)
 * - Mixed text handling
 * - Edge cases
 */
class ArabicTextNormalizerTest extends TestCase
{
    /**
     * Test that "بَقَرَة" (with diacritics) normalizes to match "بقرة" (without).
     */
    public function test_removes_diacritics_from_baqara(): void
    {
        $withDiacritics = 'بَقَرَة';
        $withoutDiacritics = 'بقره'; // Note: ة -> ه normalization

        $normalized = ArabicTextNormalizer::normalize($withDiacritics);
        $searchTerm = ArabicTextNormalizer::normalize($withoutDiacritics);

        $this->assertEquals($searchTerm, $normalized);
    }

    /**
     * Test that "بَقَرَةً" (with tanween) normalizes correctly.
     */
    public function test_removes_tanween_from_baqara(): void
    {
        $withTanween = 'بَقَرَةً';
        $expected = 'بقره'; // ة -> ه, all diacritics removed

        $normalized = ArabicTextNormalizer::normalize($withTanween);

        $this->assertEquals($expected, $normalized);
    }

    /**
     * Test that tatweel (kashida) is removed.
     */
    public function test_removes_tatweel(): void
    {
        $withTatweel = 'الـلـه';
        $withoutTatweel = 'الله';

        $this->assertEquals(
            ArabicTextNormalizer::normalize($withoutTatweel),
            ArabicTextNormalizer::normalize($withTatweel)
        );
    }

    /**
     * Test Alef variants normalization.
     */
    public function test_normalizes_alef_variants(): void
    {
        $variants = ['أحمد', 'إحمد', 'آحمد'];
        $expected = 'احمد';

        foreach ($variants as $variant) {
            $this->assertEquals(
                $expected,
                ArabicTextNormalizer::normalize($variant),
                "Failed for variant: {$variant}"
            );
        }
    }

    /**
     * Test Alef Wasla (ٱ) normalization.
     */
    public function test_normalizes_alef_wasla(): void
    {
        $withAlefWasla = 'ٱللَّه';
        $expected = 'الله';

        $this->assertEquals($expected, ArabicTextNormalizer::normalize($withAlefWasla));
    }

    /**
     * Test Alef Maksura to Yaa normalization.
     */
    public function test_normalizes_alef_maksura_to_yaa(): void
    {
        $withAlefMaksura = 'موسى';
        $expected = 'موسي';

        $this->assertEquals($expected, ArabicTextNormalizer::normalize($withAlefMaksura));
    }

    /**
     * Test Taa Marbuta to Haa normalization.
     */
    public function test_normalizes_taa_marbuta_to_haa(): void
    {
        $withTaaMarbuta = 'مكة';
        $expected = 'مكه';

        $this->assertEquals($expected, ArabicTextNormalizer::normalize($withTaaMarbuta));
    }

    /**
     * Test Waw with Hamza normalization.
     */
    public function test_normalizes_waw_with_hamza(): void
    {
        $withHamza = 'مؤمن';
        $expected = 'مومن';

        $this->assertEquals($expected, ArabicTextNormalizer::normalize($withHamza));
    }

    /**
     * Test Yaa with Hamza normalization.
     */
    public function test_normalizes_yaa_with_hamza(): void
    {
        $withHamza = 'سئل';
        $expected = 'سيل';

        $this->assertEquals($expected, ArabicTextNormalizer::normalize($withHamza));
    }

    /**
     * Test that searching "بقرة" matches text containing "بَقَرَةً".
     */
    public function test_search_term_matches_diacritized_text(): void
    {
        $searchTerm = 'بقرة';
        $textWithDiacritics = 'وَإِذْ قَالَ مُوسَىٰ لِقَوْمِهِ إِنَّ اللَّهَ يَأْمُرُكُمْ أَنْ تَذْبَحُوا بَقَرَةً';

        $normalizedSearch = ArabicTextNormalizer::prepareSearchTerm($searchTerm);
        $normalizedText = ArabicTextNormalizer::normalize($textWithDiacritics);

        $this->assertStringContainsString($normalizedSearch, $normalizedText);
    }

    /**
     * Test mixed Arabic and English text.
     */
    public function test_handles_mixed_text(): void
    {
        $mixedText = 'Hello بَقَرَة World';
        $normalized = ArabicTextNormalizer::normalize($mixedText);

        $this->assertEquals('Hello بقره World', $normalized);
    }

    /**
     * Test whitespace normalization.
     */
    public function test_normalizes_whitespace(): void
    {
        $withExtraSpaces = 'كلمة    واحدة   اثنتان';
        $expected = 'كلمه واحده اثنتان';

        $this->assertEquals($expected, ArabicTextNormalizer::normalize($withExtraSpaces));
    }

    /**
     * Test empty string handling.
     */
    public function test_handles_empty_string(): void
    {
        $this->assertEquals('', ArabicTextNormalizer::normalize(''));
    }

    /**
     * Test removeDiacritics method (without character normalization).
     */
    public function test_remove_diacritics_only(): void
    {
        $text = 'بَقَرَةً';
        $result = ArabicTextNormalizer::removeDiacritics($text);

        // Diacritics removed, but ة should still be ة (not normalized to ه)
        $this->assertEquals('بقرة', $result);
    }

    /**
     * Test createLikePattern method.
     */
    public function test_create_like_pattern(): void
    {
        $term = 'بَقَرَة';
        $pattern = ArabicTextNormalizer::createLikePattern($term);

        $this->assertEquals('%بقره%', $pattern);
    }

    /**
     * Test containsArabic method.
     */
    public function test_contains_arabic(): void
    {
        $this->assertTrue(ArabicTextNormalizer::containsArabic('Hello مرحبا'));
        $this->assertTrue(ArabicTextNormalizer::containsArabic('مرحبا'));
        $this->assertFalse(ArabicTextNormalizer::containsArabic('Hello World'));
        $this->assertFalse(ArabicTextNormalizer::containsArabic(''));
    }

    /**
     * Test complex Quranic verse normalization.
     */
    public function test_normalizes_quranic_verse(): void
    {
        $verse = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
        $normalized = ArabicTextNormalizer::normalize($verse);

        // Should remove all diacritics and normalize characters
        $this->assertEquals('بسم الله الرحمن الرحيم', $normalized);
    }

    /**
     * Test that superscript alef (ٰ) is removed.
     */
    public function test_removes_superscript_alef(): void
    {
        $text = 'الرَّحْمَٰنِ';
        $normalized = ArabicTextNormalizer::normalize($text);

        $this->assertEquals('الرحمن', $normalized);
    }

    /**
     * Test extended Quranic marks removal.
     */
    public function test_removes_quranic_marks(): void
    {
        // Small high meem, sajdah mark, etc.
        $text = 'كلمة۩ مع۟ علامات';
        $normalized = ArabicTextNormalizer::normalize($text);

        $this->assertEquals('كلمه مع علامات', $normalized);
    }

    /**
     * Test extractArabicOnly method.
     */
    public function test_extract_arabic_only(): void
    {
        $mixed = 'Hello مرحبا World عالم 123';
        $arabicOnly = ArabicTextNormalizer::extractArabicOnly($mixed);

        // Should keep only Arabic characters and spaces
        $this->assertStringContainsString('مرحبا', $arabicOnly);
        $this->assertStringContainsString('عالم', $arabicOnly);
        $this->assertStringNotContainsString('Hello', $arabicOnly);
        $this->assertStringNotContainsString('World', $arabicOnly);
        $this->assertStringNotContainsString('123', $arabicOnly);
    }
}
