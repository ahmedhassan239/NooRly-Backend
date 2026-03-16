<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use App\Domain\Auth\AppUserProfile;
use App\Domain\Users\UserOnboardingProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingProfileTest extends TestCase
{
    use RefreshDatabase;

    private AppUser $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = AppUser::factory()->create();
        AppUserProvider::create([
            'app_user_id' => $this->user->id,
            'provider' => 'email',
            'email' => 'profile@example.com',
            'password' => bcrypt('secret'),
        ]);
        AppUserProfile::create([
            'app_user_id' => $this->user->id,
            'name' => 'Profile User',
            'locale' => 'en',
        ]);
    }

    /** @test */
    public function show_returns_null_data_when_no_profile_exists()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/me/onboarding-profile');

        $response->assertStatus(200)
            ->assertJsonPath('data', null)
            ->assertJsonPath('status', true);
    }

    /** @test */
    public function update_creates_profile_and_show_returns_it()
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'display_name' => 'Ahmed',
            'embrace_islam_range' => 'born_muslim',
            'arabic_level' => 'slow',
            'prayer_level' => 'not_all_5',
            'quran_reading_level' => 'just_started',
            'goals' => ['improve_prayer', 'understand_quran'],
            'challenges' => ['remembering_to_pray', 'staying_consistent'],
            'daily_time' => 'min_15_20',
            'preferred_learning_time' => 'evening',
            'learning_style' => 'interactive',
            'reminder_preference' => 'all_reminders',
            'islam_date' => null,
        ];

        $response = $this->putJson('/api/v1/me/onboarding-profile', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.display_name', 'Ahmed')
            ->assertJsonPath('data.embrace_islam_range', 'born_muslim')
            ->assertJsonPath('data.arabic_level', 'slow')
            ->assertJsonPath('data.prayer_level', 'not_all_5')
            ->assertJsonPath('data.quran_reading_level', 'just_started')
            ->assertJsonPath('data.goals', ['improve_prayer', 'understand_quran'])
            ->assertJsonPath('data.challenges', ['remembering_to_pray', 'staying_consistent'])
            ->assertJsonPath('data.daily_time', 'min_15_20')
            ->assertJsonPath('data.preferred_learning_time', 'evening')
            ->assertJsonPath('data.learning_style', 'interactive')
            ->assertJsonPath('data.reminder_preference', 'all_reminders')
            ->assertJsonPath('data.islam_date', null)
            ->assertJsonStructure([
                'data' => [
                    'display_name',
                    'embrace_islam_range',
                    'arabic_level',
                    'prayer_level',
                    'quran_reading_level',
                    'goals',
                    'challenges',
                    'daily_time',
                    'preferred_learning_time',
                    'learning_style',
                    'reminder_preference',
                    'islam_date',
                    'onboarding_completed_at',
                ],
            ]);

        $this->assertDatabaseHas('user_onboarding_profiles', [
            'app_user_id' => $this->user->id,
            'display_name' => 'Ahmed',
        ]);

        $profile = UserOnboardingProfile::where('app_user_id', $this->user->id)->first();
        $this->assertNotNull($profile->onboarding_completed_at);
    }

    /** @test */
    public function update_is_idempotent_and_preserves_completed_at()
    {
        Sanctum::actingAs($this->user);

        $completedAt = now()->subDays(5);
        UserOnboardingProfile::create([
            'app_user_id' => $this->user->id,
            'display_name' => 'Old Name',
            'onboarding_completed_at' => $completedAt,
        ]);

        $response = $this->putJson('/api/v1/me/onboarding-profile', [
            'display_name' => 'New Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.display_name', 'New Name');

        $profile = UserOnboardingProfile::where('app_user_id', $this->user->id)->first();
        $this->assertEquals($completedAt->toDateTimeString(), $profile->onboarding_completed_at->toDateTimeString());
    }

    /** @test */
    public function show_returns_401_when_unauthenticated()
    {
        $this->getJson('/api/v1/me/onboarding-profile')->assertStatus(401);
        $this->putJson('/api/v1/me/onboarding-profile', ['display_name' => 'Test'])->assertStatus(401);
    }

    /** @test */
    public function update_rejects_invalid_enum_values()
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/me/onboarding-profile', [
            'embrace_islam_range' => 'invalid_value',
        ]);

        $response->assertStatus(422);
        $json = $response->json();
        $this->assertTrue(
            isset($json['errors']) || isset($json['meta']['errors']),
            'Response should contain validation errors',
        );
    }
}
