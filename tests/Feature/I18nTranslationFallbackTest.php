<?php

namespace Tests\Feature;

use App\Domain\Lessons\Lesson;
use App\Models\Domain\Language\Language;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class I18nTranslationFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(LanguageSeeder::class);
    }

    public function test_returns_arabic_when_translation_exists()
    {
        $lesson = Lesson::create([
            'type' => 'text',
            'duration_minutes' => 10,
        ]);

        $lesson->translations()->create([
            'language_code' => 'en',
            'title' => 'English Title',
            'content' => json_encode(['test' => 'content']),
        ]);

        $lesson->translations()->create([
            'language_code' => 'ar',
            'title' => 'عنوان عربي',
            'content' => json_encode(['test' => 'محتوى']),
        ]);

        $result = Lesson::withTranslation('ar')->first();
        
        $this->assertEquals('عنوان عربي', $result->title);
        $this->assertEquals('ar', $result->resolved_lang);
    }

    public function test_falls_back_to_english_when_arabic_missing()
    {
        $lesson = Lesson::create([
            'type' => 'text',
            'duration_minutes' => 10,
        ]);

        // Only English translation
        $lesson->translations()->create([
            'language_code' => 'en',
            'title' => 'English Title',
            'content' => json_encode(['test' => 'content']),
        ]);

        $result = Lesson::withTranslation('ar')->first();
        
        $this->assertEquals('English Title', $result->title);
        $this->assertEquals('en', $result->resolved_lang);
    }

    public function test_search_works_on_arabic_translation()
    {
        $lesson = Lesson::create([
            'type' => 'text',
            'duration_minutes' => 10,
        ]);

        $lesson->translations()->create([
            'language_code' => 'en',
            'title' => 'English Title',
            'content' => json_encode(['test' => 'content']),
        ]);

        $lesson->translations()->create([
            'language_code' => 'ar',
            'title' => 'عنوان عربي',
            'content' => json_encode(['test' => 'محتوى']),
        ]);

        $results = Lesson::withTranslation('ar')
                        ->searchTranslated('عربي')
                        ->get();
        
        $this->assertCount(1, $results);
    }

    public function test_search_falls_back_to_english_when_arabic_missing()
    {
        $lesson = Lesson::create([
            'type' => 'text',
            'duration_minutes' => 10,
        ]);

        $lesson->translations()->create([
            'language_code' => 'en',
            'title' => 'Searchable English',
            'content' => json_encode(['test' => 'content']),
        ]);

        $results = Lesson::withTranslation('ar')
                        ->searchTranslated('Searchable')
                        ->get();
        
        $this->assertCount(1, $results);
        $this->assertEquals('Searchable English', $results->first()->title);
    }

    public function test_sorting_works_with_coalesce()
    {
        // Create lessons with mixed translations
        $lesson1 = Lesson::create(['type' => 'text', 'duration_minutes' => 10]);
        $lesson1->translations()->create(['language_code' => 'en', 'title' => 'B English', 'content' => '{}']);
        $lesson1->translations()->create(['language_code' => 'ar', 'title' => 'أ عربي', 'content' => '{}']); // 'أ' comes first in Arabic

        $lesson2 = Lesson::create(['type' => 'text', 'duration_minutes' => 10]);
        $lesson2->translations()->create(['language_code' => 'en', 'title' => 'A English', 'content' => '{}']);
        // No Arabic translation

        $results = Lesson::withTranslation('ar')
                        ->orderByRaw('COALESCE(t_req.title, t_en.title) ASC')
                        ->get();
        
        $this->assertCount(2, $results);
        // First should be lesson1 (Arabic 'أ عربي')
        // Second should be lesson2 (falls back to 'A English')
    }
}
