<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\Contracts\SocialAuthProvider;
use App\Domain\Auth\Enums\Provider;
use App\Domain\Auth\Enums\RegistrationMethod;
use App\Domain\Auth\Enums\UserStatus;
use App\Domain\Auth\Services\SocialAuthProviderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\Mocks\FakeSocialAuthProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_creates_guest_user()
    {
        $response = $this->postJson('/api/v1/auth/guest', [
            'timezone' => 'Africa/Cairo',
            'country' => 'EG',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'is_guest']]);

        $this->assertDatabaseHas('app_users', [
            'is_guest' => true,
            'registration_method' => RegistrationMethod::Guest,
        ]);
    }

    public function test_register_creates_new_user()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'timezone' => 'UTC',
            'country' => 'US',
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('app_users', [
            'email' => 'test@example.com',
            'is_guest' => false,
        ]);
    }

    public function test_login_authenticates_valid_user()
    {
        $user = AppUser::factory()->create([
            'email' => 'auth@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'auth@example.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        AppUser::factory()->create([
            'email' => 'auth@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'auth@example.com',
            'password' => 'wrong',
        ]);

        // Depending on implementation, it might be 422 or 401
        // Laravel ValidationException is 422
        $response->assertStatus(422); 
    }

    public function test_google_login_links_account()
    {
        // Mock the factory to return our fake provider
        $this->mock(SocialAuthProviderFactory::class, function ($mock) {
            $mock->shouldReceive('make')
                ->with(Provider::Google)
                ->andReturn(new FakeSocialAuthProvider('google-123', 'google@example.com'));
        });

        $response = $this->postJson('/api/v1/auth/google', [
            'id_token' => 'valid-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'google@example.com');

        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'google',
            'provider_user_id' => 'google-123',
        ]);
    }

    public function test_guest_can_upgrade_via_google()
    {
        $guest = AppUser::factory()->guest()->create();
        $token = $guest->createToken('test')->plainTextToken;

        $this->mock(SocialAuthProviderFactory::class, function ($mock) {
            $mock->shouldReceive('make')
                ->with(Provider::Google)
                ->andReturn(new FakeSocialAuthProvider('google-456', 'upgrade@example.com'));
        });

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/google', [
                'id_token' => 'valid-token',
            ]);

        $response->assertOk();
        
        // Assert guest record is updated, not a new record created
        $this->assertEquals(1, AppUser::count());
        $this->assertDatabaseHas('app_users', [
            'id' => $guest->id,
            'email' => 'upgrade@example.com',
            'is_guest' => false,
        ]);
    }
}
