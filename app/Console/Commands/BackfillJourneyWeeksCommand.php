<?php

namespace App\Console\Commands;

use App\Domain\Journey\JourneyWeek;
use App\Domain\Journey\JourneyWeekLesson;
use App\Domain\Lessons\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class BackfillJourneyWeeksCommand extends Command
{
    protected $signature = 'journey:backfill-weeks
                            {--dry-run : Show what would be created without writing}
                            {--skip-existing : Skip weeks that already exist}';

    protected $description = 'Backfill journey_weeks and journey_week_lessons from lessons with day_number (1–90)';

    public function handle(): int
    {
        if (! Schema::hasColumn('lessons', 'day_number')) {
            $this->info('Lessons table no longer has day_number. Backfill only applies to legacy data. Nothing to do.');

            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $skipExisting = $this->option('skip-existing');

        if ($dryRun) {
            $this->warn('Dry run – no changes will be written.');
        }

        $lessons = Lesson::whereNotNull('day_number')
            ->where('day_number', '>=', 1)
            ->orderBy('day_number')
            ->get();

        if ($lessons->isEmpty()) {
            $this->info('No lessons with day_number found. Nothing to backfill.');

            return self::SUCCESS;
        }

        $byWeek = $lessons->groupBy(function (Lesson $lesson) {
            return (int) ceil($lesson->day_number / 7);
        });

        $created = 0;
        $skipped = 0;

        foreach ($byWeek as $weekNumber => $weekLessons) {
            if ($skipExisting && JourneyWeek::where('week_number', $weekNumber)->exists()) {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $this->line("Would create week {$weekNumber} with ".$weekLessons->count().' lessons.');
                $created++;

                continue;
            }

            $week = JourneyWeek::firstOrCreate(
                ['week_number' => $weekNumber],
                [
                    'title' => "Week {$weekNumber}",
                    'description' => null,
                    'icon' => null,
                    'is_active' => true,
                ]
            );

            foreach ($weekLessons as $lesson) {
                $dayInWeek = (($lesson->day_number - 1) % 7) + 1;
                JourneyWeekLesson::firstOrCreate(
                    [
                        'journey_week_id' => $week->id,
                        'lesson_id' => $lesson->id,
                    ],
                    [
                        'day_number' => $dayInWeek,
                        'sort_order' => $dayInWeek,
                    ]
                );
            }
            $created++;
        }

        $this->info("Processed {$created} week(s).".($skipped ? " Skipped {$skipped} existing." : ''));

        return self::SUCCESS;
    }
}
