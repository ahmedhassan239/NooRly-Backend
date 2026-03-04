<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Password reset link in emails: always HTTPS on main domain for mobile + web fallback.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $email = urlencode($notifiable->getEmailForPasswordReset());
            $base = rtrim(config('app.frontend_url', config('app.url')), '/');

            return "{$base}/reset-password?token={$token}&email={$email}";
        });

        \App\Domain\Quran\Models\QuranAyah::observe(\App\Observers\ExternalContentObserver::class);
        \App\Domain\Hadith\Models\HadithItem::observe(\App\Observers\ExternalContentObserver::class);

        // Observer for auto-normalizing Arabic text in verse_texts
        \App\Domain\QuranAllLang\Models\VerseText::observe(\App\Observers\VerseTextObserver::class);

        // Clear content scopes cache when scopes are created/updated/deleted
        \App\Domain\ContentScopes\ContentScope::observe(\App\Observers\ContentScopeObserver::class);
    }
}
