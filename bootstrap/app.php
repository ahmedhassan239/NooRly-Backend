<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified.email' => \App\Http\Middleware\EnsureEmailVerified::class,
            'campaign.admin' => \App\Http\Middleware\EnsureCampaignAdminAppUser::class,
        ]);
        
        $middleware->api(prepend: [
            \App\Http\Middleware\SetRequestLanguage::class,
            \App\Http\Middleware\UpdateLastActiveMiddleware::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Check user journey progress and generate milestone notifications
        $schedule->command('notifications:milestones')->dailyAt('02:00');

        // Generate occasion notifications (Friday reminder, Ramadan, Eid)
        $schedule->command('notifications:occasions')->dailyAt('03:00');

        // Detect inactive users (3/7 days) and generate support notifications
        $schedule->command('notifications:inactive')->dailyAt('04:00');

        // Admin marketing / manual push campaigns (scheduled send_mode)
        $schedule->command('notifications:process-scheduled-campaigns')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
