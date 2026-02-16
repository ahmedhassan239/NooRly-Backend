<?php

namespace App\Services\Auth;

use App\Domain\Auth\AppUser;
use App\Mail\EmailOtpCodeMail;
use App\Models\EmailOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Exception;
use Carbon\Carbon;

class EmailOtpService
{
    /**
     * Send OTP to the given email address.
     * Use this for resend requests where we don't have the user object yet, or just email.
     */
    public function sendOtpByEmail(string $email): void
    {
        $email = strtolower(trim($email));
        
        // Check if user exists with this email
        // We use AppUserProvider since auth is typically via provider
        // Assuming email is stored in AppUserProvider or AppUser itself depending on logic.
        // Based on RegisterAction, AppUserProvider stores the email for 'email' provider.
        $userProvider = \App\Domain\Auth\AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if (!$userProvider) {
            // Do nothing if user not found to prevent enumeration, but return success message in controller
            return;
        }

        $user = $userProvider->user;

        if (!$user) {
            return;
        }

        $this->sendOtpForUser($user, $email);
    }

    /**
     * Send OTP to a specific user.
     */
    public function sendOtpForUser(AppUser $user, ?string $email = null): void
    {
        // If email not provided, try to find it from providers
        if (!$email) {
            $provider = $user->providers()->where('provider', 'email')->first();
            $email = $provider ? $provider->email : null;
        }

        if (!$email) {
             throw new Exception("User does not have an email address.");
        }
        
        $email = strtolower(trim($email));

        if ($user->email_verified_at) {
            // Already verified, do nothing
            return;
        }

        // 1. Check Resend Cooldown (Last sent within 60s?)
        $lastOtp = EmailOtp::where('user_id', $user->id)
            ->where('email', $email)
            ->latest('last_sent_at')
            ->first();

        if ($lastOtp && $lastOtp->last_sent_at && $lastOtp->last_sent_at->gt(now()->subSeconds(60))) {
             throw new Exception("Please wait before requesting another OTP.");
        }

        // 2. Check Rate Limit (5 per hour)
        $rateLimitKey = 'otp:resend:' . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
             throw new Exception("Too many OTP requests. Please try again later.", 429);
        }
        RateLimiter::hit($rateLimitKey, 3600); // 1 hour decay

        // 3. Generate and Store OTP
        $otpPlain = (string) random_int(100000, 999999);
        $otpHash = Hash::make($otpPlain);
        $expiresAt = now()->addMinutes(10);

        // We can create a new record for every OTP to keep history, or update.
        // The requirements say "Upsert OTP record", but also have `attempts` count.
        // A new record for each fresh OTP is cleaner for history and 'attempts' reset.
        // However, to prevent table bloat, we could update if an unused valid one exists, 
        // OR just create new and let old ones rot (cleanup job later).
        // Let's create new to simplify "fresh attempts" logic.
        
        $emailOtp = EmailOtp::create([
            'user_id' => $user->id,
            'email' => $email,
            'otp_hash' => $otpHash,
            'expires_at' => $expiresAt,
            'attempts' => 0,
            'resend_count' => ($lastOtp ? $lastOtp->resend_count : 0) + 1,
            'last_sent_at' => now(),
            'used_at' => null,
        ]);

        // 4. Send Email
        Mail::to($email)->send(new EmailOtpCodeMail($otpPlain, $expiresAt));
    }

    /**
     * Verify the OTP.
     * Returns the User if successful, throws Exception if not.
     */
    public function verifyOtp(string $email, string $otp): AppUser
    {
        $email = strtolower(trim($email));

        // 1. Check Rate Limit for Verification (Brute Force Protection)
        $rateLimitKey = 'otp:verify:' . $email;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            throw new Exception("Too many verification attempts. Please try again in 10 minutes.", 429);
        }
        RateLimiter::hit($rateLimitKey, 600); // 10 minutes decay

        // 2. Find User
        $userProvider = \App\Domain\Auth\AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if (!$userProvider || !$userProvider->user) {
             throw new Exception("Invalid email address.");
        }
        $user = $userProvider->user;

        // 3. Find Latest Valid OTP
        $otpRecord = EmailOtp::where('user_id', $user->id)
            ->where('email', $email)
            ->whereNull('used_at')
            ->latest('created_at')
            ->first();

        if (!$otpRecord) {
            throw new Exception("Invalid or expired OTP.");
        }

        // 4. Check Expiration
        if ($otpRecord->expires_at->isPast()) {
            throw new Exception("OTP has expired.");
        }

        // 5. Check Max Attempts
        if ($otpRecord->attempts >= 5) {
            throw new Exception("Too many failed attempts. Please request a new OTP.");
        }

        // 6. Verify Hash
        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            throw new Exception("Invalid OTP.");
        }

        // 7. Success
        $otpRecord->update(['used_at' => now()]);
        
        // Mark user as verified if not already
        if (!$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        // Clear rate limits mostly for cleanup, though not strictly required
        RateLimiter::clear('otp:verify:' . $email);
        
        return $user;
    }
}
