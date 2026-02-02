<?php

namespace App\Console\Commands;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseVerseTranslations extends Command
{
    protected $signature = 'quran:diagnose-verse {verse_id : The verse ID to diagnose}';
    protected $description = 'Diagnose verse translations to find why English is missing';

    public function handle()
    {
        $verseId = $this->argument('verse_id');
        
        $this->info("=== Diagnosing Verse ID: {$verseId} ===\n");
        
        // Step 1: Check language statuses
        $this->info("1. Language Statuses:");
        $languages = DB::connection('mysql_quran_all_lang')
            ->table('languages')
            ->whereIn('code', ['ar', 'en', 'bn'])
            ->get(['id', 'code', 'name', 'is_active']);
        
        foreach ($languages as $lang) {
            $status = $lang->is_active ? '✓ ACTIVE' : '✗ INACTIVE';
            $this->line("   {$lang->code} ({$lang->name}): {$status}");
        }
        
        // Step 2: Check verse_texts for this verse
        $this->info("\n2. Verse Texts for verse_id={$verseId}:");
        $verseTexts = DB::connection('mysql_quran_all_lang')
            ->table('verse_texts')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('verse_texts.verse_id', $verseId)
            ->select(
                'verse_texts.id',
                'verse_texts.verse_id',
                'languages.code as lang_code',
                'languages.name as lang_name',
                'languages.is_active',
                'translations.source_name',
                DB::raw('LEFT(verse_texts.text, 50) as text_preview')
            )
            ->orderBy('languages.code')
            ->get();
        
        if ($verseTexts->isEmpty()) {
            $this->error("   No verse texts found for verse_id={$verseId}");
            return 1;
        }
        
        foreach ($verseTexts as $vt) {
            $active = $vt->is_active ? '✓' : '✗';
            $this->line("   {$active} {$vt->lang_code} ({$vt->lang_name}) - {$vt->source_name}: {$vt->text_preview}...");
        }
        
        // Step 3: Test Eloquent query (what the View page uses)
        $this->info("\n3. Eloquent Query Result (orderByLanguagePriority):");
        $verse = QuranVerse::find($verseId);
        if (!$verse) {
            $this->error("   Verse not found!");
            return 1;
        }
        
        $eloquentTexts = $verse->verseTexts()
            ->orderByLanguagePriority()
            ->with(['translation.language'])
            ->get();
        
        $this->line("   Found " . $eloquentTexts->count() . " translations:");
        foreach ($eloquentTexts as $vt) {
            $lang = $vt->translation->language;
            $active = $lang->is_active ? '✓' : '✗';
            $this->line("   {$active} {$lang->code} ({$lang->name}) - {$vt->translation->source_name}");
        }
        
        // Step 4: Test raw SQL that orderByLanguagePriority generates
        $this->info("\n4. Raw SQL Query (what orderByLanguagePriority executes):");
        $rawSql = DB::connection('mysql_quran_all_lang')
            ->table('verse_texts')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('verse_texts.verse_id', $verseId)
            ->where('languages.is_active', true)
            ->select('verse_texts.*', 'languages.code as language_code')
            ->orderByRaw("CASE WHEN languages.code = 'en' THEN 1 WHEN languages.code = 'ar' THEN 2 ELSE 3 END")
            ->toSql();
        
        $this->line("   SQL: " . $rawSql);
        
        $rawResults = DB::connection('mysql_quran_all_lang')
            ->table('verse_texts')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('verse_texts.verse_id', $verseId)
            ->where('languages.is_active', true)
            ->select('verse_texts.id', 'languages.code as lang_code')
            ->orderByRaw("CASE WHEN languages.code = 'en' THEN 1 WHEN languages.code = 'ar' THEN 2 ELSE 3 END")
            ->get();
        
        $this->line("   Raw query returns " . $rawResults->count() . " results:");
        foreach ($rawResults as $r) {
            $this->line("   - verse_text_id={$r->id}, lang={$r->lang_code}");
        }
        
        // Step 5: Check if English translation exists but is linked to inactive language
        $this->info("\n5. Checking English translation linkage:");
        $englishTranslation = DB::connection('mysql_quran_all_lang')
            ->table('verse_texts')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('verse_texts.verse_id', $verseId)
            ->where('languages.code', 'en')
            ->select('verse_texts.id', 'languages.is_active', 'translations.id as translation_id')
            ->first();
        
        if ($englishTranslation) {
            $status = $englishTranslation->is_active ? 'ACTIVE' : 'INACTIVE';
            $this->line("   English translation EXISTS (verse_text_id={$englishTranslation->id}, translation_id={$englishTranslation->translation_id})");
            $this->line("   Language status: {$status}");
        } else {
            $this->error("   English translation DOES NOT EXIST for this verse!");
        }
        
        return 0;
    }
}
