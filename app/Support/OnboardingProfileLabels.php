<?php

namespace App\Support;

/**
 * Human-readable labels for onboarding profile enum values (Filament, exports, etc.).
 */
final class OnboardingProfileLabels
{
    private const EMBRACE_ISLAM = [
        'less_than_1_month' => 'Less than 1 month',
        'months_1_to_6' => '1–6 months',
        'months_6_to_12' => '6–12 months',
        'years_1_to_2' => '1–2 years',
        'over_2_years' => '2+ years',
        'born_muslim' => 'Born Muslim',
    ];

    private const ARABIC_LEVEL = [
        'fluent' => 'Yes, fluently',
        'slow' => 'Yes, slowly',
        'learning_now' => 'Learning now',
        'wants_to_learn' => 'No, but want to learn',
        'none' => 'No',
    ];

    private const PRAYER_LEVEL = [
        'regular' => 'Yes, regularly',
        'not_all_5' => 'Yes, but not all 5',
        'learning' => 'Learning to pray',
        'not_yet' => 'Not yet',
    ];

    private const QURAN_LEVEL = [
        'regular' => 'Yes, regularly',
        'occasional' => 'Yes, occasionally',
        'just_started' => 'Just started',
        'not_yet' => 'Not yet',
    ];

    private const GOALS = [
        'learn_basics' => 'Learn the basics',
        'improve_prayer' => 'Improve my prayer',
        'understand_quran' => 'Understand Quran',
        'build_good_habits' => 'Build good habits',
        'connect_with_community' => 'Connect with community',
    ];

    private const CHALLENGES = [
        'understanding_arabic' => 'Understanding Arabic',
        'remembering_to_pray' => 'Remembering to pray',
        'finding_time' => 'Finding time to learn',
        'staying_consistent' => 'Staying consistent',
        'dealing_with_doubts' => 'Dealing with doubts',
        'lack_of_support' => 'Lack of support',
    ];

    private const DAILY_TIME = [
        'min_5_10' => '5–10 min',
        'min_15_20' => '15–20 min',
        'min_30_plus' => '30+ min',
        'flexible' => 'Flexible',
    ];

    private const LEARNING_TIME = [
        'morning' => 'Morning',
        'afternoon' => 'Afternoon',
        'evening' => 'Evening',
        'night' => 'Night',
        'anytime' => 'Anytime',
    ];

    private const LEARNING_STYLE = [
        'reading' => 'Reading',
        'listening' => 'Listening',
        'videos' => 'Videos',
        'interactive' => 'Interactive',
        'mix' => 'Mix of all',
    ];

    private const REMINDER = [
        'all_reminders' => 'All reminders',
        'prayer_only' => 'Prayer times only',
        'customize_later' => 'Let me customize',
        'none' => 'No thanks',
    ];

    public static function embraceIslam(?string $value): ?string
    {
        return $value === null ? null : (self::EMBRACE_ISLAM[$value] ?? $value);
    }

    public static function arabicLevel(?string $value): ?string
    {
        return $value === null ? null : (self::ARABIC_LEVEL[$value] ?? $value);
    }

    public static function prayerLevel(?string $value): ?string
    {
        return $value === null ? null : (self::PRAYER_LEVEL[$value] ?? $value);
    }

    public static function quranLevel(?string $value): ?string
    {
        return $value === null ? null : (self::QURAN_LEVEL[$value] ?? $value);
    }

    public static function goal(string $value): string
    {
        return self::GOALS[$value] ?? $value;
    }

    public static function goals(?array $values): string
    {
        if ($values === null || $values === []) {
            return '—';
        }
        return implode(', ', array_map([self::class, 'goal'], $values));
    }

    public static function challenge(string $value): string
    {
        return self::CHALLENGES[$value] ?? $value;
    }

    public static function challenges(?array $values): string
    {
        if ($values === null || $values === []) {
            return '—';
        }
        return implode(', ', array_map([self::class, 'challenge'], $values));
    }

    public static function dailyTime(?string $value): ?string
    {
        return $value === null ? null : (self::DAILY_TIME[$value] ?? $value);
    }

    public static function learningTime(?string $value): ?string
    {
        return $value === null ? null : (self::LEARNING_TIME[$value] ?? $value);
    }

    public static function learningStyle(?string $value): ?string
    {
        return $value === null ? null : (self::LEARNING_STYLE[$value] ?? $value);
    }

    public static function reminder(?string $value): ?string
    {
        return $value === null ? null : (self::REMINDER[$value] ?? $value);
    }
}
