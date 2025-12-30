<?php

namespace App\Filament\Widgets;

use App\Services\DatabaseHealthService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DatabaseConnectionStats extends BaseWidget
{
    protected static ?int $sort = 0;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $health = new DatabaseHealthService();
        $connections = ['mysql', 'mysql_quran', 'mysql_hadith'];
        $stats = [];

        foreach ($connections as $conn) {
            $status = $health->checkConnection($conn);
            $isOk = $status['status'] === 'ok';
            $icon = $isOk ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';
            $color = $isOk ? 'success' : 'danger';
            $label = $isOk ? "{$status['latency']}ms" : "Failed";
            
            $stats[] = Stat::make(strtoupper($conn), $isOk ? 'Online' : 'Offline')
                ->description($isOk ? "DB: {$status['database']} ($label)" : $status['message'])
                ->descriptionIcon($icon)
                ->color($color);
        }

        return $stats;
    }
}
