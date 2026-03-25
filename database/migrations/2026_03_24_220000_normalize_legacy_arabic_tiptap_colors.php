<?php

use App\Support\Html\LegacyTiptapHtmlNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lesson_translations')
            ->where('language_code', 'ar')
            ->whereNotNull('content')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = LegacyTiptapHtmlNormalizer::normalizeLegacyArabicHtml(
                        is_string($row->content) ? $row->content : null
                    );

                    if (is_string($normalized) && $normalized !== $row->content) {
                        DB::table('lesson_translations')
                            ->where('id', $row->id)
                            ->update(['content' => $normalized]);
                    }
                }
            });

        DB::table('daily_task_translations')
            ->where('language_code', 'ar')
            ->whereNotNull('description')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $normalized = LegacyTiptapHtmlNormalizer::normalizeLegacyArabicHtml(
                        is_string($row->description) ? $row->description : null
                    );

                    if (is_string($normalized) && $normalized !== $row->description) {
                        DB::table('daily_task_translations')
                            ->where('id', $row->id)
                            ->update(['description' => $normalized]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Irreversible content normalization.
    }
};

