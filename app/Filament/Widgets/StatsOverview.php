<?php

namespace App\Filament\Widgets;

use App\Domain\Auth\AppUser;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = AppUser::count();
        $activeUsers = AppUser::where('is_guest', false)->count();
        $emailRegistrations = AppUser::where('is_guest', false)
            ->whereDoesntHave('socialAccounts')
            ->count();
        $googleSignIns = AppUser::whereHas('socialAccounts', function ($query) {
            $query->where('provider', 'google');
        })->count();
        $facebookSignIns = AppUser::whereHas('socialAccounts', function ($query) {
            $query->where('provider', 'facebook');
        })->count();
        $appleSignIns = AppUser::whereHas('socialAccounts', function ($query) {
            $query->where('provider', 'apple');
        })->count();
        $guestUsers = AppUser::where('is_guest', true)->count();

        return [
            Stat::make('Total App Users', $totalUsers)
                ->description('All registered and guest users')
                ->descriptionIcon('heroicon-m-users')
                ->color('warning')
                ->chart([5, 10, 15, 20, 25, 30, $totalUsers]),
            
            Stat::make('Active Users', $activeUsers)
                ->description('Currently active users')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Email Registrations', $emailRegistrations)
                ->description('Users registered via email')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('info'),
            
            Stat::make('Google Sign-ins', $googleSignIns)
                ->description('Users registered via Google')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),
            
            Stat::make('Facebook Sign-ins', $facebookSignIns)
                ->description('Users registered via Facebook')
                ->descriptionIcon('heroicon-s-globe-alt')
                ->color('primary'),
            
            Stat::make('Apple Sign-ins', $appleSignIns)
                ->description('Users registered via Apple')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('warning'),
            
            Stat::make('Guest Users', $guestUsers)
                ->description('Anonymous guest users')
                ->descriptionIcon('heroicon-m-user-circle')
                ->color('gray'),
        ];
    }
}
