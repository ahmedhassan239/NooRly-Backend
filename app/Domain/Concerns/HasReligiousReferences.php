<?php

namespace App\Domain\Concerns;

/**
 * Trait HasReligiousReferences
 * 
 * Provides helper methods for syncing religious references from HTML content.
 * Models should already have quranAyahs() and hadithItems() relationships defined.
 */
trait HasReligiousReferences
{
    /**
     * Sync religious references from HTML content.
     * 
     * Parses the content for references and syncs them to pivot tables.
     * 
     * @param string $htmlContent The HTML content to parse
     * @return void
     */
    public function syncReligiousReferencesFromContent(string $htmlContent): void
    {
        $renderer = app(\App\Services\Religious\ReligiousReferenceRenderer::class);
        $references = $renderer->extractReferenceIds($htmlContent);

        // Sync Quran Ayahs
        if (!empty($references['ayah'])) {
            $this->syncQuranAyahs($references['ayah']);
        } else {
            // Use manual detach for cross-database relationships
            $modelType = get_class($this);
            $modelId = $this->id;
            if ($modelId) {
                \Illuminate\Support\Facades\DB::connection('mysql')
                    ->table('quran_ayahables')
                    ->where('ayahable_type', $modelType)
                    ->where('ayahable_id', $modelId)
                    ->delete();
            }
        }

        // Sync Hadith Items
        if (!empty($references['hadith'])) {
            $this->syncHadithItems($references['hadith']);
        } else {
            // Use manual detach for cross-database relationships
            $modelType = get_class($this);
            $modelId = $this->id;
            if ($modelId) {
                \Illuminate\Support\Facades\DB::connection('mysql')
                    ->table('hadith_itemables')
                    ->where('hadithable_type', $modelType)
                    ->where('hadithable_id', $modelId)
                    ->delete();
            }
        }
    }

    /**
     * Sync Quran Ayahs manually (for cross-database relationships).
     */
    protected function syncQuranAyahs(array $ayahIds): void
    {
        $modelType = get_class($this);
        $modelId = $this->id;

        if (!$modelId) {
            return;
        }

        // Use explicit connection to ensure we're using the main database
        $db = \Illuminate\Support\Facades\DB::connection('mysql');

        // Delete existing relationships
        $db->table('quran_ayahables')
            ->where('ayahable_type', $modelType)
            ->where('ayahable_id', $modelId)
            ->delete();

        // Insert new relationships
        if (!empty($ayahIds)) {
            $insertData = array_map(function ($ayahId) use ($modelType, $modelId) {
                return [
                    'quran_ayah_id' => $ayahId,
                    'ayahable_type' => $modelType,
                    'ayahable_id' => $modelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $ayahIds);

            $db->table('quran_ayahables')->insert($insertData);
        }
    }

    /**
     * Sync Hadith Items manually (for cross-database relationships).
     */
    protected function syncHadithItems(array $hadithIds): void
    {
        $modelType = get_class($this);
        $modelId = $this->id;

        if (!$modelId) {
            return;
        }

        // Use explicit connection to ensure we're using the main database
        $db = \Illuminate\Support\Facades\DB::connection('mysql');

        // Delete existing relationships
        $db->table('hadith_itemables')
            ->where('hadithable_type', $modelType)
            ->where('hadithable_id', $modelId)
            ->delete();

        // Insert new relationships
        if (!empty($hadithIds)) {
            $insertData = array_map(function ($hadithId) use ($modelType, $modelId) {
                return [
                    'hadith_item_id' => $hadithId,
                    'hadithable_type' => $modelType,
                    'hadithable_id' => $modelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $hadithIds);

            $db->table('hadith_itemables')->insert($insertData);
        }
    }
}
