<?php

namespace App\Services\Auth;

use App\Domain\Auth\AppUserProvider;
use App\Models\EmailOtp;
use App\Models\PasswordResetOtpSession;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Exception;

class PasswordResetService
{
    private const RESET_SESSION_EXPIRE_MINUTES = 10;

    public function __construct(
        private readonly EmailOtpService $emailOtpService
    ) {}

    /**
     * Request password reset OTP.
     * Does not reveal whether the account exists.
     */
    public function requestOtp(string $email): void
    {
        $email = strtolower(trim($email));
        $provider = $this->findEmailPasswordProvider($email);

        if (! $provider || ! $provider->user) {
            return;
        }

        // Additional endpoint-aware throttling (in addition to route throttle)
        $requestKey = 'password-reset:request:'.$email;
        if (RateLimiter::tooManyAttempts($requestKey, 5)) {
            throw new Exception('Too many requests. Please try again later.', 429);
        }
        RateLimiter::hit($requestKey, 60);

        $this->emailOtpService->sendOtpForUser(
            $provider->user,
            $email,
            EmailOtp::PURPOSE_PASSWORD_RESET
        );
    }

    /**
     * Verify OTP and issue short-lived reset session token.
     *
     * @return array{reset_token: string, expires_in: int}
     */
    public function verifyOtp(string $email, string $otp): array
    {
        $email = strtolower(trim($email));
        $provider = $this->findEmailPasswordProvider($email);

        if (! $provider || ! $provider->user) {
            throw new Exception('Invalid or expired verification code.');
        }

        $result = $this->emailOtpService->verifyPasswordResetOtp($email, $otp);
        $otpRecord = $result['otp'];
        $user = $result['user'];

        $plainResetToken = Str::random(64);
        $expiresAt = now()->addMinutes(self::RESET_SESSION_EXPIRE_MINUTES);

        PasswordResetOtpSession::create([
            'user_id' => $user->id,
            'email_otp_id' => $otpRecord->id,
            'email' => $email,
            'token_hash' => Hash::make($plainResetToken),
            'expires_at' => $expiresAt,
            'used_at' => null,
        ]);

        return [
            'reset_token' => $plainResetToken,
            'expires_in' => self::RESET_SESSION_EXPIRE_MINUTES * 60,
        ];
    }

    /**
     * Reset password with verified reset token.
     */
    public function resetWithVerifiedToken(string $email, string $verifiedToken, string $password): void
    {
        $email = strtolower(trim($email));
        $provider = $this->findEmailPasswordProvider($email);
        if (! $provider || ! $provider->user) {
            throw new Exception('Invalid reset request.');
        }

        $session = PasswordResetOtpSession::where('user_id', $provider->user->id)
            ->where('email', $email)
            ->whereNull('used_at')
            ->latest('created_at')
            ->first();

        if (! $session || $session->expires_at->isPast()) {
            throw new Exception('Reset session is invalid or expired.');
        }

        if (! Hash::check($verifiedToken, $session->token_hash)) {
            throw new Exception('Reset session is invalid or expired.');
        }

        $provider->update(['password' => Hash::make($password)]);
        $session->update(['used_at' => now()]);

        // Invalidate all existing API sessions/tokens after password change.
        $provider->user->tokens()->delete();
    }

    private function findEmailPasswordProvider(string $email): ?AppUserProvider
    {
        return AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->whereNotNull('password')
            ->first();
    }
}
