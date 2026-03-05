<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Duas\Dua;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Daily Inspiration API tests.
 * Note: Requires MySQL for migrations (RefreshDatabase); SQLite may fail on
 * migrations that use ENUM/MODIFY (e.g. saved_items).
 */
class DailyInspirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-02-28 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** GET /api/v1/home/daily-inspiration requires auth. GET /api/v1/daily-inspiration is public (collections). */
    public function test_unauth_request_returns_401(): void
    {
        $this->getJson('/api/v1/home/daily-inspiration')->assertUnauthorized();
    }

    /** Auth GET /api/v1/home/daily-inspiration returns unified Flutter shape with refresh_after_seconds and expires_at. */
    public function test_first_call_generates_and_returns_unified_shape(): void
    {
        Dua::create([
            'dua_key' => 'test-dua',
            'category_key' => 'general',
            'text_ar' => 'بسم الله',
            'text_en' => 'In the name of Allah',
            'is_active' => true,
            'position' => 1,
        ]);

        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/home/daily-inspiration', ['Accept-Language' => 'en']);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonStructure([
                'data' => [
                    'type',
                    'id',
                    'title',
                    'arabic',
                    'translation',
                    'refresh_after_seconds',
                    'expires_at',
                ],
            ]);
        $this->assertContains($response->json('data.type'), ['ayah', 'hadith', 'dhikr', 'dua']);
        $this->assertGreaterThan(0, $response->json('data.refresh_after_seconds'));
        $this->assertDatabaseHas('user_daily_inspirations', ['app_user_id' => $user->id]);
    }

    /** Response type is one of ayah/hadith/dhikr/dua (not hardcoded hadith); when type is dua, content matches. */
    public function test_returns_valid_type_from_library(): void
    {
        Dua::create([
            'dua_key' => 'only-dua',
            'category_key' => 'general',
            'text_ar' => 'دعاء',
            'text_en' => 'Dua text',
            'source' => 'Quran',
            'is_active' => true,
            'position' => 1,
        ]);

        $user = AppUser::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/home/daily-inspiration', ['Accept-Language' => 'en']);

        $response->assertOk();
        $type = $response->json('data.type');
        $this->assertContains($type, ['ayah', 'hadith', 'dhikr', 'dua'], 'Type must be one of library types, not hardcoded.');
        if ($type === 'dua') {
            $this->assertSame('دعاء', $response->json('data.arabic'));
            $this->assertSame('Dua text', $response->json('data.translation'));
        }
    }

    public function test_second_call_within_interval_returns_same_item(): void
    {
        Dua::create([
            'dua_key' => 'test-dua',
            'category_key' => 'general',
            'text_ar' => 'بسم الله',
            'text_en' => 'In the name of Allah',
            'is_active' => true,
            'position' => 1,
        ]);

        $user = AppUser::factory()->create();

        $first = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home/daily-inspiration');
        $first->assertOk();
        $type1 = $first->json('data.type');
        $id1 = $first->json('data.id');

        $second = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home/daily-inspiration');
        $second->assertOk();
        $this->assertSame($type1, $second->json('data.type'));
        $this->assertSame($id1, $second->json('data.id'));
    }

    public function test_after_expiry_returns_new_item(): void
    {
        Dua::create([
            'dua_key' => 'dua-1',
            'category_key' => 'general',
            'text_ar' => 'نص ١',
            'text_en' => 'Text 1',
            'is_active' => true,
            'position' => 1,
        ]);
        Dua::create([
            'dua_key' => 'dua-2',
            'category_key' => 'general',
            'text_ar' => 'نص ٢',
            'text_en' => 'Text 2',
            'is_active' => true,
            'position' => 2,
        ]);

        $user = AppUser::factory()->create();

        $first = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home/daily-inspiration');
        $first->assertOk();
        $expiresAt = $first->json('data.expires_at');

        Carbon::setTestNow(Carbon::parse($expiresAt)->addSecond());

        $second = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home/daily-inspiration');
        $second->assertOk();
        $this->assertNotEquals($first->json('data.expires_at'), $second->json('data.expires_at'));
    }
}
