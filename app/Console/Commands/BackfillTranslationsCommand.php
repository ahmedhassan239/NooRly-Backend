<?php

namespace App\Console\Commands;

use App\Domain\DailyTask;
use App\Domain\Duas\Dua;
use App\Domain\Faq\Faq;
use App\Domain\Faq\FaqCategory;
use App\Domain\Lessons\Lesson;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTranslationsCommand extends Command
{
    protected $signature = 'i18n:backfill {--dry-run : Preview changes without applying}';

    protected $description = 'Backfill existing content into English translations';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        DB::beginTransaction();

        try {
            // Backfill Lessons
            $this->info('Backfilling Lessons...');
            $lessons = Lesson::all();
            foreach ($lessons as $lesson) {
                if ($lesson->translations()->where('language_code', 'en')->exists()) {
                    $this->warn("Lesson {$lesson->id} already has EN translation, skipping");
                    continue;
                }

                if (!$dryRun) {
                    $lesson->translations()->create([
                        'language_code' => 'en',
                        'title' => $lesson->title ?? 'Untitled',
                        'short_description' => $lesson->short_description ?? null,
                        'content' => $lesson->content ?? '{}',
                    ]);
                }
                $this->line("  ✓ Lesson {$lesson->id}: '{$lesson->title}'");
            }

            // Backfill Duas
            $this->info('Backfilling Duas...');
            $duas = Dua::all();
            foreach ($duas as $dua) {
                if ($dua->translations()->where('language_code', 'en')->exists()) {
                    continue;
                }

                if (!$dryRun) {
                    $dua->translations()->create([
                        'language_code' => 'en',
                        'title' => $dua->title ?? 'Untitled',
                        'translation_text' => $dua->translation ?? '',
                        'transliteration' => $dua->transliteration ?? null,
                        'category' => $dua->category ?? null,
                    ]);
                }
                $this->line("  ✓ Dua {$dua->id}: '{$dua->title}'");
            }

            // Backfill Daily Tasks
            $this->info('Backfilling Daily Tasks...');
            $tasks = DailyTask::all();
            foreach ($tasks as $task) {
                if ($task->translations()->where('language_code', 'en')->exists()) {
                    continue;
                }

                if (!$dryRun) {
                    $task->translations()->create([
                        'language_code' => 'en',
                        'title' => $task->title ?? 'Untitled',
                        'description' => $task->description ?? null,
                    ]);
                }
                $this->line("  ✓ Task {$task->id}: '{$task->title}'");
            }

            // Backfill FAQ Categories
            $this->info('Backfilling FAQ Categories...');
            $categories = FaqCategory::all();
            foreach ($categories as $category) {
                if ($category->translations()->where('language_code', 'en')->exists()) {
                    continue;
                }

                if (!$dryRun) {
                    $category->translations()->create([
                        'language_code' => 'en',
                        'name' => $category->name ?? 'Untitled',
                    ]);
                }
                $this->line("  ✓ Category {$category->id}: '{$category->name}'");
            }

            // Backfill FAQs
            $this->info('Backfilling FAQs...');
            $faqs = Faq::all();
            foreach ($faqs as $faq) {
                if ($faq->translations()->where('language_code', 'en')->exists()) {
                    continue;
                }

                if (!$dryRun) {
                    $faq->translations()->create([
                        'language_code' => 'en',
                        'question' => $faq->question ?? 'Question?',
                        'answer' => $faq->answer ?? 'Answer.',
                    ]);
                }
                $this->line("  ✓ FAQ {$faq->id}: '{$faq->question}'");
            }

            if ($dryRun) {
                DB::rollBack();
                $this->warn('DRY RUN completed - no changes were made');
            } else {
                DB::commit();
                $this->info('✅ Backfill completed successfully!');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Backfill failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
