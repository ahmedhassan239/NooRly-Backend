<?php

namespace Tests\Feature;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\QuranAllLang\Models\Translation;
use App\Domain\QuranAllLang\Models\VerseText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranAllLangVerseFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Note: This test assumes the quran_all_lang database exists
        // In a real scenario, you might want to use a test database or mock
    }

    public function test_active_languages_only_appear_in_verse_details()
    {
        // This test verifies that only active languages appear in verse texts
        // Note: This is a conceptual test - actual implementation depends on your test DB setup
        
        // Arrange: Set up languages
        $arLang = Language::updateOrCreate(
            ['code' => 'ar'],
            ['name' => 'Arabic', 'is_rtl' => true, 'is_active' => true]
        );
        
        $enLang = Language::updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'is_rtl' => false, 'is_active' => true]
        );
        
        $bnLang = Language::updateOrCreate(
            ['code' => 'bn'],
            ['name' => 'Bengali', 'is_rtl' => false, 'is_active' => false] // INACTIVE
        );
        
        // Create translations
        $arTranslation = Translation::updateOrCreate(
            ['language_id' => $arLang->id, 'source_name' => 'Original'],
            ['file_name' => 'arabic.csv']
        );
        
        $enTranslation = Translation::updateOrCreate(
            ['language_id' => $enLang->id, 'source_name' => 'Sahih International'],
            ['file_name' => 'english.csv']
        );
        
        $bnTranslation = Translation::updateOrCreate(
            ['language_id' => $bnLang->id, 'source_name' => 'Bengali Translation'],
            ['file_name' => 'bengali.csv']
        );
        
        // Create a verse
        $verse = QuranVerse::updateOrCreate(
            ['ayah_key' => '1:1'],
            ['surah_number' => 1, 'ayah_number' => 1]
        );
        
        // Create verse texts
        VerseText::updateOrCreate(
            ['verse_id' => $verse->id, 'translation_id' => $arTranslation->id],
            ['text' => 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ']
        );
        
        VerseText::updateOrCreate(
            ['verse_id' => $verse->id, 'translation_id' => $enTranslation->id],
            ['text' => 'In the name of Allah, the Entirely Merciful, the Especially Merciful.']
        );
        
        VerseText::updateOrCreate(
            ['verse_id' => $verse->id, 'translation_id' => $bnTranslation->id],
            ['text' => 'Bengali text here']
        );
        
        // Act: Get verse texts using orderByLanguagePriority
        $activeTexts = $verse->verseTexts()
            ->orderByLanguagePriority()
            ->get();
        
        // Assert: Only active languages (ar, en) should appear, not bn
        $this->assertCount(2, $activeTexts, 'Should only return 2 active language translations');
        
        $langCodes = $activeTexts->map(function ($vt) {
            $vt->load('translation.language');
            return $vt->translation->language->code;
        })->toArray();
        
        $this->assertContains('ar', $langCodes, 'Arabic should be present');
        $this->assertContains('en', $langCodes, 'English should be present');
        $this->assertNotContains('bn', $langCodes, 'Bengali should NOT be present when inactive');
        
        // Assert: English should be first (priority ordering)
        $firstLang = $activeTexts->first();
        $firstLang->load('translation.language');
        $this->assertEquals('en', $firstLang->translation->language->code, 'English should be first due to priority');
    }

    public function test_preview_shows_english_when_available()
    {
        // This test verifies that preview prioritizes English > Arabic > others
        
        $arLang = Language::updateOrCreate(
            ['code' => 'ar'],
            ['name' => 'Arabic', 'is_rtl' => true, 'is_active' => true]
        );
        
        $enLang = Language::updateOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'is_rtl' => false, 'is_active' => true]
        );
        
        $arTranslation = Translation::updateOrCreate(
            ['language_id' => $arLang->id, 'source_name' => 'Original'],
            ['file_name' => 'arabic.csv']
        );
        
        $enTranslation = Translation::updateOrCreate(
            ['language_id' => $enLang->id, 'source_name' => 'Sahih International'],
            ['file_name' => 'english.csv']
        );
        
        $verse = QuranVerse::updateOrCreate(
            ['ayah_key' => '1:2'],
            ['surah_number' => 1, 'ayah_number' => 2]
        );
        
        VerseText::updateOrCreate(
            ['verse_id' => $verse->id, 'translation_id' => $arTranslation->id],
            ['text' => 'Arabic text']
        );
        
        VerseText::updateOrCreate(
            ['verse_id' => $verse->id, 'translation_id' => $enTranslation->id],
            ['text' => 'English text for preview']
        );
        
        // Get preview (first text)
        $texts = $verse->verseTexts()
            ->orderByLanguagePriority()
            ->get();
        
        $firstText = $texts->first();
        $firstText->load('translation.language');
        
        $this->assertEquals('en', $firstText->translation->language->code, 'Preview should show English first');
        $this->assertEquals('English text for preview', $firstText->text);
    }
}
