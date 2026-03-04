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

    public function test_unauth_request_returns_401(): void
    {
        $response = $this->getJson('/api/v1/home/daily-inspiration');
        $response->assertUnauthorized();
    }

    public function test_first_call_generates_and_returns_item(): void
    {
        Dua::create([
            'dua_key' => 'test-dua',
            'text_ar' => 'بسم الله',
            'text_en' => 'In the name of Allah',
            'is_active' => true,
            'position' => 1,
        ]);

        $user = AppUser::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/home/daily-inspiration');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'type',
                    'refresh_after_seconds',
                    'expires_at',
                    'item' => [
                        'id',
                        'text',
                    ],
                ],
            ])
            ->assertJsonPath('data.type', fn ($v) => in_array($v, ['hadith', 'verse', 'dua', 'adhkar'], true))
            ->assertJsonPath('data.refresh_after_seconds', fn ($v) => $v > 0);

        $this->assertDatabaseHas('user_daily_inspirations', [
            'app_user_id' => $user->id,
        ]);
    }

    public function test_second_call_within_interval_returns_same_item(): void
    {
        Dua::create([
            'dua_key' => 'test-dua',
            'text_ar' => 'بسم الله',
            'text_en' => 'In the name of Allah',
            'is_active' => true,
            'position' => 1,
        ]);

        $user = AppUser::factory()->create();

        $first = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home/daily-inspiration');
        $first->assertOk();
        $type1 = $first->json('data.type');
        $id1 = $first->json('data.item.id');

        $second = $this->actingAs($user, 'sanctum')->getJson('/api/v1/home/daily-inspiration');
        $second->assertOk();
        $this->assertSame($type1, $second->json('data.type'));
        $this->assertSame($id1, $second->json('data.item.id'));
    }

    public function test_after_expiry_returns_new_item(): void
    {
        Dua::create([
            'dua_key' => 'dua-1',
            'text_ar' => 'نص ١',
            'text_en' => 'Text 1',
            'is_active' => true,
            'position' => 1,
        ]);
        Dua::create([
            'dua_key' => 'dua-2',
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
