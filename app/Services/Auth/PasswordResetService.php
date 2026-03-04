<?php

namespace App\Services\Auth;

use App\Domain\Auth\AppUserProvider;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    private const TOKEN_EXPIRE_MINUTES = 60;

    /**
     * Send password reset link to email.
     * Does not reveal whether the email exists; always returns without throwing.
     */
    public function sendResetLink(string $email): void
    {
        $email = strtolower(trim($email));

        $provider = AppUserProvider::where('provider', 'email')
            ->where('email', $email)
            ->first();

        if (! $provider) {
            return;
        }

        $token = Str::random(64);
        $hashed = Hash::make($token);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => $hashed,
                'created_at' => now(),
            ]
        );

        $resetUrl = $this->buildResetUrl($email, $token);

        Mail::to($email)->send(new PasswordResetMail($resetUrl, self::TOKEN_EXPIRE_MINUTES));
    }

    /**
     * Reset password using token.
     *
     * @throws \Exception
     */
    public function reset(string $email, string $token, string $password): void
    {
        $email = strtolower(trim($email));

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (! $record || ! Hash::check($token, $record->token)) {
            throw new \Exception('This password reset link is invalid or has expired.');
        }

        $createdAt = $record->created_at ? \Carbon\Carbon::parse($record->created_at) : null;
        if ($createdAt && $createdAt->addMinutes(self::TOKEN_EXPIRE_MINUTES)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new \Exception('This password reset link has expired.');
        }

        $provider = AppUserProvider::where('provider', 'email')->where('email', $email)->first();

        if (! $provider) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new \Exception('Invalid reset request.');
        }

        $provider->update(['password' => Hash::make($password)]);
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }

    private function buildResetUrl(string $email, string $token): string
    {
        $query = http_build_query([
            'token' => $token,
            'email' => $email,
        ]);

        $frontendUrl = config('app.frontend_url');
        if (! empty($frontendUrl)) {
            return rtrim($frontendUrl, '/').'/reset-password?'.$query;
        }

        $scheme = config('app.password_reset_scheme', 'myapp');
        $path = config('app.password_reset_path', 'reset-password');

        return $scheme.'://'.$path.'?'.$query;
    }
}
