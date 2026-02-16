<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use App\Domain\Auth\AppUserProfile;
use App\Domain\Users\AppUserOnboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function me_returns_correct_name_and_email_from_registration()
    {
        $user = AppUser::factory()->create();
        AppUserProvider::create([
            'app_user_id' => $user->id,
            'provider' => 'email',
            'email' => 'registered@example.com',
            'password' => bcrypt('secret'),
        ]);
        AppUserProfile::create([
            'app_user_id' => $user->id,
            'name' => 'Registered User',
            'gender' => 'male',
            'birth_date' => '1990-05-15',
            'locale' => 'ar',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Registered User')
            ->assertJsonPath('data.email', 'registered@example.com')
            ->assertJsonPath('data.gender', 'male')
            ->assertJsonPath('data.birth_date', '1990-05-15')
            ->assertJsonPath('data.locale', 'ar')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'uuid',
                    'status',
                    'name',
                    'email',
                    'gender',
                    'birth_date',
                    'locale',
                    'profile',
                ],
            ]);
    }

    /** @test */
    public function me_includes_onboarding_summary_when_onboarding_exists()
    {
        $user = AppUser::factory()->create();
        AppUserProvider::create([
            'app_user_id' => $user->id,
            'provider' => 'email',
            'email' => 'me@example.com',
            'password' => bcrypt('secret'),
        ]);
        AppUserProfile::create([
            'app_user_id' => $user->id,
            'name' => 'Me User',
            'locale' => 'en',
        ]);
        AppUserOnboarding::create([
            'app_user_id' => $user->id,
            'start_date' => now(),
            'current_step' => 'goals',
            'summary_completed' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.onboarding.completed', false)
            ->assertJsonPath('data.onboarding.current_step', 'goals');
    }

    /** @test */
    public function me_requires_authentication()
    {
        $this->getJson('/api/v1/me')->assertStatus(401);
    }
}
