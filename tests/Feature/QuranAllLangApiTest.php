<?php

namespace Tests\Feature;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\QuranAllLang\Models\Translation;
use App\Domain\QuranAllLang\Models\VerseText;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuranAllLangApiTest extends TestCase
{
    use RefreshDatabase;

    // We cannot use RefreshDatabase because we are using a separate external database
    // that persists. Instead, we should clean up created data in tearDown or use transactions if possible.
    // For this test, we will create unique data to avoid conflicts.

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_languages()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/quran-all-lang/languages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'code', 'name', 'is_rtl'],
                ],
            ]);
    }

    public function test_can_create_and_delete_language()
    {
        $code = 'test_' . rand(100, 999);
        
        // Create
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/admin/quran-all-lang/languages', [
                'code' => $code,
                'name' => 'Test Language',
                'is_rtl' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.code', $code);

        $id = $response->json('data.id');

        // Delete
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/admin/quran-all-lang/languages/{$id}");

        $response->assertStatus(200);
        
        // precise verification
        $this->assertNull(Language::find($id));
    }

    public function test_can_list_verses_filtered()
    {
        // Ensure at least one verse exists (from real DB)
        // We know Surah 1 Ayah 1 exists in the seeded/imported DB
        
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/admin/quran-all-lang/verses?surah=1&ayah=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'surah_number', 'ayah_number'],
                ],
            ]);
            
        $this->assertTrue(count($response->json('data')) > 0);
    }

    public function test_can_get_verse_details_with_translations()
    {
        // Get first available verse
        $verse = QuranVerse::first();
        
        if (!$verse) {
            $this->markTestSkipped('No verses found in database.');
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/admin/quran-all-lang/verses/{$verse->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'surah_number',
                    'translations', // should be grouped
                ],
            ]);
    }
}
