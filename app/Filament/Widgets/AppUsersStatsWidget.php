<?php

namespace App\Filament\Widgets;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\Enums\RegistrationMethod;
use App\Domain\Auth\Enums\UserStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AppUsersStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalUsers = AppUser::count();
        $activeUsers = AppUser::where('status', UserStatus::Active)->count();

        $emailUsers = AppUser::whereHas('providers', fn ($q) => $q->where('provider', 'email'))->count();
        $googleUsers = AppUser::whereHas('providers', fn ($q) => $q->where('provider', 'google'))->count();
        $facebookUsers = AppUser::whereHas('providers', fn ($q) => $q->where('provider', 'facebook'))->count();
        $appleUsers = AppUser::whereHas('providers', fn ($q) => $q->where('provider', 'apple'))->count();
        $guestUsers = AppUser::whereHas('providers', fn ($q) => $q->where('provider', 'guest'))->count();

        return [
            Stat::make('Total App Users', $totalUsers)
                ->description('All registered and guest users')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Active Users', $activeUsers)
                ->description('Currently active users')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Email Registrations', $emailUsers)
                ->description('Users registered via email')
                ->descriptionIcon('heroicon-o-envelope')
                ->color('info'),
            Stat::make('Google Sign-ins', $googleUsers)
                ->description('Users registered via Google')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('success'),
            Stat::make('Facebook Sign-ins', $facebookUsers)
                ->description('Users registered via Facebook')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('info'),
            Stat::make('Apple Sign-ins', $appleUsers)
                ->description('Users registered via Apple')
                ->descriptionIcon('heroicon-o-device-phone-mobile')
                ->color('warning'),
            Stat::make('Guest Users', $guestUsers)
                ->description('Anonymous guest users')
                ->descriptionIcon('heroicon-o-user-circle')
                ->color('secondary'),
        ];
    }
}
