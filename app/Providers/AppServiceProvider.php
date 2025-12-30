<?php

namespace App\Providers;

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
        \App\Domain\Quran\Models\QuranAyah::observe(\App\Observers\ExternalContentObserver::class);
        \App\Domain\Hadith\Models\HadithItem::observe(\App\Observers\ExternalContentObserver::class);
    }
}
