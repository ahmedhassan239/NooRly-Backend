<?php

namespace App\Domain\Prayers\Services;

use App\Domain\Auth\AppUser;
use App\Domain\Prayers\Contracts\PrayerTimeProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AladhanPrayerTimeProvider implements PrayerTimeProvider
{
    public function getTimesForUser(AppUser $user, Carbon $date): array
    {
        // In a real app, user model would have lat/lng or city/country
        // For now, defaulting to London for demo if not present, or using a fallback
        $city = 'London';
        $country = 'UK';

        try {
            $response = Http::timeout(3)->get("http://api.aladhan.com/v1/timingsByCity/{$date->format('d-m-Y')}", [
                'city' => $city,
                'country' => $country,
                'method' => 2, // ISNA
            ]);

            if ($response->successful()) {
                $timings = $response->json('data.timings');

                // Filter only mandatory prayers
                return [
                    'Fajr' => $timings['Fajr'],
                    'Dhuhr' => $timings['Dhuhr'],
                    'Asr' => $timings['Asr'],
                    'Maghrib' => $timings['Maghrib'],
                    'Isha' => $timings['Isha'],
                ];
            }
        } catch (\Exception $e) {
            Log::error('Aladhan API failed: '.$e->getMessage());
        }

        // Fallback or empty if failed
        return [
            'Fajr' => '05:00',
            'Dhuhr' => '12:00',
            'Asr' => '15:30',
            'Maghrib' => '18:00',
            'Isha' => '20:00',
        ];
    }
}
