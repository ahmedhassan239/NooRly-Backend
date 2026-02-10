<?php

namespace Tests\Feature\Api\V1;

use App\Support\Arabic\ArabicTextNormalizer;
use Tests\TestCase;

class ArabicSearchTest extends TestCase
{
    /** @test */
    public function it_normalizes_arabic_text_by_removing_diacritics()
    {
        // Text with diacritics (tashkeel)
        $textWithDiacritics = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
        
        // Expected normalized text (without diacritics)
        $normalized = ArabicTextNormalizer::normalize($textWithDiacritics);
        
        // Should not contain any diacritics
        $this->assertStringNotContainsString('ِ', $normalized); // Kasra
        $this->assertStringNotContainsString('ْ', $normalized); // Sukun
        $this->assertStringNotContainsString('ّ', $normalized); // Shadda
        $this->assertStringNotContainsString('َ', $normalized); // Fatha
        
        // Should still contain the base letters
        $this->assertStringContainsString('بسم', $normalized);
        $this->assertStringContainsString('الله', $normalized);
    }

    /** @test */
    public function it_normalizes_alef_variants()
    {
        // Different Alef forms
        $text1 = 'أحمد'; // Alef with Hamza above
        $text2 = 'إبراهيم'; // Alef with Hamza below
        $text3 = 'آدم'; // Alef with Madda
        
        $normalized1 = ArabicTextNormalizer::normalize($text1);
        $normalized2 = ArabicTextNormalizer::normalize($text2);
        $normalized3 = ArabicTextNormalizer::normalize($text3);
        
        // All should start with plain Alef (ا)
        $this->assertStringStartsWith('ا', $normalized1);
        $this->assertStringStartsWith('ا', $normalized2);
        $this->assertStringStartsWith('ا', $normalized3);
    }

    /** @test */
    public function it_normalizes_taa_marbuta_to_haa()
    {
        $text = 'البقرة';
        $normalized = ArabicTextNormalizer::normalize($text);
        
        // ة should become ه
        $this->assertStringContainsString('ه', $normalized);
        $this->assertStringNotContainsString('ة', $normalized);
    }

    /** @test */
    public function it_removes_tatweel()
    {
        $text = 'اللـــه'; // With tatweel
        $normalized = ArabicTextNormalizer::normalize($text);
        
        // Should not contain tatweel
        $this->assertStringNotContainsString('ـ', $normalized);
    }

    /** @test */
    public function it_detects_arabic_text()
    {
        $this->assertTrue(ArabicTextNormalizer::containsArabic('مرحبا'));
        $this->assertTrue(ArabicTextNormalizer::containsArabic('Hello مرحبا World'));
        $this->assertFalse(ArabicTextNormalizer::containsArabic('Hello World'));
        $this->assertFalse(ArabicTextNormalizer::containsArabic('12345'));
    }

    /** @test */
    public function it_creates_like_pattern_for_search()
    {
        $term = 'بَقَرَة';
        $pattern = ArabicTextNormalizer::createLikePattern($term);
        
        // Should be wrapped with wildcards
        $this->assertStringStartsWith('%', $pattern);
        $this->assertStringEndsWith('%', $pattern);
        
        // Should be normalized (no diacritics)
        $this->assertStringNotContainsString('َ', $pattern);
    }

    /** @test */
    public function search_term_without_diacritics_matches_text_with_diacritics()
    {
        // Simulate what happens in the database
        $storedText = 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
        $storedNormalized = ArabicTextNormalizer::normalize($storedText);
        
        // User searches without diacritics
        $searchTerm = 'بسم الله';
        $searchNormalized = ArabicTextNormalizer::normalize($searchTerm);
        
        // The normalized stored text should contain the normalized search term
        $this->assertStringContainsString($searchNormalized, $storedNormalized);
    }

    /** @test */
    public function it_handles_empty_and_whitespace_text()
    {
        $this->assertEquals('', ArabicTextNormalizer::normalize(''));
        $this->assertEquals('', ArabicTextNormalizer::normalize('   '));
        $this->assertEquals('مرحبا', ArabicTextNormalizer::normalize('  مرحبا  '));
    }

    /** @test */
    public function it_collapses_multiple_spaces()
    {
        $text = 'كلمة    أخرى';
        $normalized = ArabicTextNormalizer::normalize($text);
        
        // Should have single space
        $this->assertStringNotContainsString('  ', $normalized);
    }

    /** @test */
    public function quran_search_endpoint_accepts_arabic_without_diacritics()
    {
        // Skip if database not available
        if (!$this->canConnectToQuranDatabase()) {
            $this->markTestSkipped('Quran database not available');
        }

        // Search without diacritics
        $response = $this->getJson('/api/v1/quran/search?q=بسم');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => [
                    'query',
                ],
            ]);

        // The query should be preserved in meta
        $this->assertEquals('بسم', $response->json('meta.query'));
    }

    /** @test */
    public function hadith_search_endpoint_accepts_arabic_without_diacritics()
    {
        // Skip if database not available
        if (!$this->canConnectToHadithDatabase()) {
            $this->markTestSkipped('Hadith database not available');
        }

        $response = $this->getJson('/api/v1/hadith/search?q=الله');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data',
                'meta' => [
                    'query',
                ],
            ]);
    }

    /**
     * Check if we can connect to the Quran database
     */
    private function canConnectToQuranDatabase(): bool
    {
        try {
            \DB::connection('mysql_quran_all_lang')->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if we can connect to the Hadith database
     */
    private function canConnectToHadithDatabase(): bool
    {
        try {
            $connection = config('content_sources.hadith.connection', 'mysql_hadith');
            \DB::connection($connection)->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
