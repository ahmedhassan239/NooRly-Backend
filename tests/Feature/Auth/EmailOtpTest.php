<?php

namespace Tests\Feature\Auth;

use App\Domain\Auth\AppUser;
use App\Models\EmailOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Mail\EmailOtpCodeMail;
use Tests\TestCase;
use Carbon\Carbon;

class EmailOtpTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_registration_triggers_otp_and_does_not_return_token()
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'needs_email_verification' => true,
                    'email' => 'test@example.com',
                ]
            ])
            ->assertJsonMissing(['token']);

        $this->assertDatabaseHas('app_users', ['email_verified_at' => null]);
        $this->assertDatabaseHas('app_user_providers', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('email_otps', ['email' => 'test@example.com']);

        Mail::assertSent(EmailOtpCodeMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_login_triggers_otp_for_unverified_user()
    {
        Mail::fake();

        $user = AppUser::factory()->create(['email_verified_at' => null]);
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'needs_email_verification' => true,
                    'email' => 'test@example.com',
                ]
            ])
            ->assertJsonMissing(['token']);

        Mail::assertSent(EmailOtpCodeMail::class);
    }

    public function test_register_same_email_unverified_returns_needs_verification()
    {
        Mail::fake();

        $user = AppUser::factory()->create(['email_verified_at' => null]);
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'existing@example.com',
            'password' => Hash::make('oldpass'),
        ]);
        $user->profile()->create(['name' => 'Existing', 'locale' => 'en']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'New Name',
            'email' => 'existing@example.com',
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'data' => [
                    'needs_email_verification' => true,
                    'email' => 'existing@example.com',
                ],
            ])
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseCount('app_users', 1);
        Mail::assertSent(EmailOtpCodeMail::class);
    }

    public function test_register_same_email_verified_returns_409()
    {
        $user = AppUser::factory()->create(['email_verified_at' => now()]);
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'taken@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->profile()->create(['name' => 'Taken', 'locale' => 'en']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Other',
            'email' => 'taken@example.com',
            'password' => 'otherpass123',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status' => false,
                'message' => 'Email already registered',
            ]);

        $this->assertDatabaseCount('app_users', 1);
    }

    public function test_register_with_gender_and_birth_date_stores_in_profile()
    {
        Mail::fake();

        $birthDate = '1990-05-15';

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'gendered@example.com',
            'password' => 'password123',
            'gender' => 'female',
            'birth_date' => $birthDate,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'needs_email_verification' => true,
                    'email' => 'gendered@example.com',
                ],
            ]);

        $this->assertDatabaseHas('app_user_providers', ['email' => 'gendered@example.com']);
        $provider = \App\Domain\Auth\AppUserProvider::where('provider', 'email')
            ->where('email', 'gendered@example.com')->first();
        $user = $provider->user;
        $this->assertNotNull($user);
        $this->assertNotNull($user->profile);
        $this->assertSame('female', $user->profile->gender);
        $this->assertSame($birthDate, $user->profile->birth_date?->format('Y-m-d'));
    }

    public function test_register_without_gender_and_birth_date_stores_defaults()
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $provider = \App\Domain\Auth\AppUserProvider::where('provider', 'email')
            ->where('email', 'minimal@example.com')->first();
        $user = $provider->user;
        $this->assertNotNull($user);
        $this->assertNotNull($user->profile);
        $this->assertSame('unknown', $user->profile->gender);
        $this->assertNull($user->profile->birth_date);
    }

    public function test_verify_otp_success()
    {
        $user = AppUser::factory()->create(['email_verified_at' => null]);
        $provider = $user->providers()->create([
            'provider' => 'email',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $otpPlain = '123456';
        EmailOtp::create([
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'otp_hash' => Hash::make($otpPlain),
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/email/verify-otp', [
            'email' => 'test@example.com',
            'otp' => $otpPlain,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'user']
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('email_otps', ['email' => 'test@example.com', 'used_at' => now()]);
    }

    public function test_verify_otp_failure_wrong_code()
    {
        $user = AppUser::factory()->create(['email_verified_at' => null]);
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/auth/email/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '654321', // Wrong code
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid OTP.']); // Depends on exception message

        $this->assertDatabaseHas('email_otps', ['attempts' => 1]);
    }

    public function test_verify_otp_failure_expired()
    {
        $user = AppUser::factory()->create(['email_verified_at' => null]);
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(), // Expired
        ]);

        $response = $this->postJson('/api/v1/auth/email/verify-otp', [
            'email' => 'test@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'OTP has expired.']);
    }

    public function test_send_otp_always_returns_generic_success()
    {
        $user = AppUser::factory()->create(['email_verified_at' => null]);
        $user->providers()->create([
            'provider' => 'email',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        // send-otp always returns 200 with generic message (cooldown not exposed)
        $response = $this->postJson('/api/v1/auth/email/send-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'If the email exists, an OTP has been sent.',
            ]);
    }

    public function test_middleware_blocks_unverified_user()
    {
        $user = AppUser::factory()->create(['email_verified_at' => null]);
        
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Email is not verified.']);
    }

    public function test_middleware_allows_verified_user()
    {
        $user = AppUser::factory()->create(['email_verified_at' => now()]);
        
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200);
    }
}
