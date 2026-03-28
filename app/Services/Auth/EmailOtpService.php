<?php

namespace App\Services\Auth;

use App\Domain\Auth\AppUser;
use App\Mail\EmailOtpCodeMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Exception;

class EmailOtpService
{
    /**
     * Send OTP to the given email address.
     * Use this for resend requests where we don't have the user object yet, or just email.
     */
    public function sendOtpByEmail(string $email): void
    {
        $email = strtolower(trim($email));

        $userProvider = \App\Domain\Auth\AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if (! $userProvider) {
            return;
        }

        $user = $userProvider->user;

        if (! $user) {
            return;
        }

        $this->sendOtpForUser($user, $email, EmailOtp::PURPOSE_EMAIL_VERIFICATION);
    }

    /**
     * Send OTP to a specific user.
     */
    public function sendOtpForUser(
        AppUser $user,
        ?string $email = null,
        string $purpose = EmailOtp::PURPOSE_EMAIL_VERIFICATION
    ): void
    {
        if (! $email) {
            $provider = $user->providers()->where('provider', 'email')->first();
            $email = $provider ? $provider->email : null;
        }

        if (! $email) {
            throw new Exception('User does not have an email address.');
        }

        $email = strtolower(trim($email));

        if ($purpose === EmailOtp::PURPOSE_EMAIL_VERIFICATION && $user->email_verified_at) {
            return;
        }

        $lastOtp = EmailOtp::where('user_id', $user->id)
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->latest('last_sent_at')
            ->first();

        if ($lastOtp && $lastOtp->last_sent_at && $lastOtp->last_sent_at->gt(now()->subSeconds(60))) {
            throw new Exception('Please wait before requesting another OTP.', 429);
        }

        $rateLimitKey = 'otp:resend:'.$purpose.':'.$email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            throw new Exception('Too many OTP requests. Please try again later.', 429);
        }
        RateLimiter::hit($rateLimitKey, 3600);

        $otpPlain = (string) random_int(100000, 999999);
        $otpHash = Hash::make($otpPlain);
        $expiresAt = now()->addMinutes(10);

        EmailOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'purpose' => $purpose,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'resend_count' => ($lastOtp ? (int) $lastOtp->resend_count : 0) + 1,
            'last_sent_at' => now(),
            'used_at' => null,
        ]);

        Mail::to($email)->send(new EmailOtpCodeMail($otpPlain, $expiresAt, $purpose));
    }

    /**
     * Verify the OTP.
     * Returns the User if successful, throws Exception if not.
     */
    public function verifyOtp(string $email, string $otp): AppUser
    {
        $result = $this->verifyOtpRecord($email, $otp, EmailOtp::PURPOSE_EMAIL_VERIFICATION, 10, 600);
        $user = $result['user'];

        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        RateLimiter::clear('otp:verify:'.EmailOtp::PURPOSE_EMAIL_VERIFICATION.':'.strtolower(trim($email)));

        return $user;
    }

    /**
     * Verify password-reset OTP only and return otp record + user.
     *
     * @return array{user: AppUser, otp: EmailOtp}
     *
     * @throws Exception
     */
    public function verifyPasswordResetOtp(string $email, string $otp): array
    {
        return $this->verifyOtpRecord($email, $otp, EmailOtp::PURPOSE_PASSWORD_RESET, 5, 600);
    }

    /**
     * @return array{user: AppUser, otp: EmailOtp}
     */
    private function verifyOtpRecord(
        string $email,
        string $otp,
        string $purpose,
        int $maxRateAttempts,
        int $rateDecaySeconds
    ): array {
        $email = strtolower(trim($email));

        $rateLimitKey = 'otp:verify:'.$purpose.':'.$email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxRateAttempts)) {
            throw new Exception('Too many verification attempts. Please try again later.', 429);
        }
        RateLimiter::hit($rateLimitKey, $rateDecaySeconds);

        $userProvider = \App\Domain\Auth\AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if (! $userProvider || ! $userProvider->user) {
            throw new Exception('Invalid or expired verification code.');
        }
        $user = $userProvider->user;

        $otpRecord = EmailOtp::where('user_id', $user->id)
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->latest('created_at')
            ->first();

        if (! $otpRecord) {
            throw new Exception('Invalid or expired verification code.');
        }

        if ($otpRecord->expires_at->isPast()) {
            throw new Exception('Verification code has expired.');
        }

        if ((int) $otpRecord->attempts >= 5) {
            throw new Exception('Too many failed attempts. Please request a new code.');
        }

        if (! Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            throw new Exception('Invalid verification code.');
        }

        $otpRecord->update(['used_at' => now()]);

        return ['user' => $user, 'otp' => $otpRecord];
    }
}
