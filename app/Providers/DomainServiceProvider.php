<?php

namespace App\Providers;

use App\Domain\Prayers\Contracts\PrayerTimeProvider;
use App\Domain\Prayers\Services\AladhanPrayerTimeProvider;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PrayerTimeProvider::class, AladhanPrayerTimeProvider::class);
    }

    public function boot(): void
    {
        //
    }
}
