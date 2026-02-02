<?php

namespace App\Providers;

use App\Contracts\HadithSearchServiceInterface;
use App\Contracts\QuranSearchServiceInterface;
use App\Domain\Prayers\Contracts\PrayerTimeProvider;
use App\Domain\Prayers\Services\AladhanPrayerTimeProvider;
use App\Services\Hadith\HadithSearchService;
use App\Services\Quran\QuranSearchService;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Prayer time provider
        $this->app->bind(PrayerTimeProvider::class, AladhanPrayerTimeProvider::class);

        // Search services for Categories module
        $this->app->bind(QuranSearchServiceInterface::class, QuranSearchService::class);
        $this->app->bind(HadithSearchServiceInterface::class, HadithSearchService::class);
    }

    public function boot(): void
    {
        //
    }
}
