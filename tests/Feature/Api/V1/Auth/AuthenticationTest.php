<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\AppUserProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_register_new_user()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'name' => 'Test User',
            'locale' => 'en',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Authenticated successfully',
            ])
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => [
                        'uuid',
                        'email', // Depending on resource
                    ]
                ],
                'meta'
            ]);

        $this->assertDatabaseHas('app_users', [
            'status' => 'active'
        ]);
        
        $this->assertDatabaseHas('app_user_providers', [
            'email' => 'test@example.com',
            'provider' => 'email'
        ]);
    }

    #[Test]
    public function it_can_login_existing_user()
    {
        // Create user logic manually or via factory if available.
        // Since factories might not be set up for AppUserProvider, doing it manually.
        
        $user = AppUser::create([
            'status' => 'active',
            'last_active_at' => now(),
        ]);
        
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'login@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->profile()->create([
            'name' => 'Login User',
            'locale' => 'en',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'login@example.com');
    }

    #[Test]
    public function it_fails_login_with_wrong_password()
    {
         $user = AppUser::create([
            'status' => 'active',
            'last_active_at' => now(),
        ]);
        
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'wrong@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    #[Test]
    public function it_can_get_me()
    {
        $user = AppUser::create(['status' => 'active']);
        $user->profile()->create(['name' => 'Me User', 'locale' => 'en']);
        
        // Create a token (Sanctum)
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Me User');
    }

    #[Test]
    public function it_can_login_social_google()
    {
        // Mock the ID token verification to return valid data
        // We can't easily mock the new instance inside the action unless we bind it.
        // But our Providers use Http facade, so we can mock Http.

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => config('services.google.client_id'),
                'sub' => 'google_123',
                'email' => 'google@test.com',
                'name' => 'Google Test',
                'picture' => 'avatar.jpg',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/auth/social/google', [
            'id_token' => 'valid_token_mock',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.email', 'google@test.com');
            
        $this->assertDatabaseHas('app_user_providers', [
            'provider' => 'google',
            'provider_user_id' => 'google_123'
        ]);
    }
    
    #[Test]
    public function it_can_check_health()
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'status' => 'ok'
                ],
            ]);
    }
}
