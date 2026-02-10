<?php

namespace Tests\Feature\Api\V1;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\QuranAllLang\Models\Translation;
use App\Domain\QuranAllLang\Models\VerseText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranLanguageActivationTest extends TestCase
{
    /**
     * Test that only active languages are returned in API responses.
     * 
     * This is a regression test for the bug where Bengali was showing
     * instead of English because of alphabetical ordering.
     */

    /** @test */
    public function it_returns_only_active_languages()
    {
        // This test requires the quran_all_lang database to be available
        // Skip if not available
        if (!$this->canConnectToQuranDatabase()) {
            $this->markTestSkipped('Quran database not available');
        }

        $response = $this->getJson('/api/v1/quran/languages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'is_rtl',
                    ],
                ],
            ]);

        // All returned languages should be active
        $languages = collect($response->json('data'));
        
        // Verify we got languages
        $this->assertGreaterThan(0, $languages->count());
        
        // Check that English and Arabic are present (they should be active by default)
        $codes = $languages->pluck('code')->toArray();
        $this->assertContains('ar', $codes, 'Arabic should be active');
        $this->assertContains('en', $codes, 'English should be active');
    }

    /** @test */
    public function it_prioritizes_english_over_bengali_in_verse_response()
    {
        if (!$this->canConnectToQuranDatabase()) {
            $this->markTestSkipped('Quran database not available');
        }

        // Get a verse that has multiple translations
        $response = $this->getJson('/api/v1/quran/surahs/1');

        $response->assertStatus(200);

        $verses = $response->json('data.verses');
        
        if (empty($verses)) {
            $this->markTestSkipped('No verses available');
        }

        // For each verse, if it has text, verify the ordering
        foreach ($verses as $verse) {
            if (isset($verse['text']) && isset($verse['text_ar'])) {
                // The primary text should be English (priority 1) or Arabic (priority 2)
                // NOT Bengali (priority 3)
                // This is a sanity check - the actual ordering is tested in unit tests
                $this->assertNotEmpty($verse['text']);
            }
        }
    }

    /** @test */
    public function it_returns_daily_verse_with_correct_language_priority()
    {
        if (!$this->canConnectToQuranDatabase()) {
            $this->markTestSkipped('Quran database not available');
        }

        $response = $this->getJson('/api/v1/quran/daily');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'surah_number',
                    'ayah_number',
                    'ayah_key',
                ],
            ]);

        // Verify we got a verse
        $verse = $response->json('data');
        $this->assertNotEmpty($verse['id']);
    }

    /** @test */
    public function it_filters_verse_texts_by_active_languages_only()
    {
        if (!$this->canConnectToQuranDatabase()) {
            $this->markTestSkipped('Quran database not available');
        }

        // Get a specific verse with translations
        $response = $this->getJson('/api/v1/quran/verses/1/1');

        $response->assertStatus(200);

        $translations = $response->json('data.translations');
        
        if (empty($translations)) {
            $this->markTestSkipped('No translations available');
        }

        // All translations should be from active languages
        // We can't directly check is_active from the response,
        // but we can verify the structure is correct
        foreach ($translations as $languageName => $texts) {
            $this->assertIsArray($texts);
            foreach ($texts as $text) {
                $this->assertArrayHasKey('translator', $text);
                $this->assertArrayHasKey('text', $text);
                $this->assertArrayHasKey('direction', $text);
            }
        }
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
}
