<?php

namespace App\Domain\Duas\Services;

use App\Domain\Duas\Dua;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DuasIngestionService
{
    public function ingest(array $langs = ['ar', 'en']): array
    {
        $stats = [
            'total_processed' => 0,
            'created' => 0,
            'updated' => 0,
        ];

        // Load JSON files
        $arData = $this->loadJsonFile('ar');
        $enData = $this->loadJsonFile('en');

        // Merge by dua_key
        $merged = $this->mergeByKey($arData, $enData);

        Log::info("Processing {count} duas", ['count' => count($merged)]);

        // Upsert into database
        foreach ($merged as $duaKey => $data) {
            $this->validateDuaData($data);

            DB::transaction(function () use ($data, &$stats) {
                $existing = Dua::where('dua_key', $data['dua_key'])->first();

                Dua::updateOrCreate(
                    ['dua_key' => $data['dua_key']],
                    [
                        'category_key' => $data['category_key'] ?? 'general',
                        'source' => $data['source'] ?? null,
                        'text_ar' => $data['text_ar'],
                        'transliteration' => $data['transliteration'] ?? null,
                        'text_en' => $data['text_en'] ?? null,
                        'meta' => $data['meta'] ?? null,
                    ]
                );

                if ($existing) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
                $stats['total_processed']++;
            });
        }

        return $stats;
    }

    private function loadJsonFile(string $lang): array
    {
        $path = "content/duas/{$lang}.json";

        if (!Storage::exists($path)) {
            Log::warning("Duas file not found: {$path}");
            return [];
        }

        $content = Storage::get($path);
        return json_decode($content, true) ?? [];
    }

    private function mergeByKey(array $arData, array $enData): array
    {
        $merged = [];

        // Index by dua_key
        foreach ($arData as $dua) {
            $key = $dua['dua_key'] ?? null;
            if (!$key) continue;

            $merged[$key] = [
                'dua_key' => $key,
                'category_key' => $dua['category_key'] ?? 'general',
                'source' => $dua['source'] ?? null,
                'text_ar' => $dua['text_ar'] ?? $dua['arabic'] ?? '',
                'transliteration' => $dua['transliteration'] ?? null,
                'text_en' => null,
                'meta' => $dua['meta'] ?? null,
            ];
        }

        // Merge English translations
        foreach ($enData as $dua) {
            $key = $dua['dua_key'] ?? null;
            if (!$key) continue;

            if (!isset($merged[$key])) {
                $merged[$key] = [
                    'dua_key' => $key,
                    'category_key' => $dua['category_key'] ?? 'general',
                    'source' => $dua['source'] ?? null,
                    'text_ar' => '',
                    'transliteration' => $dua['transliteration'] ?? null,
                    'text_en' => $dua['text_en'] ?? $dua['translation'] ?? null,
                    'meta' => $dua['meta'] ?? null,
                ];
            } else {
                $merged[$key]['text_en'] = $dua['text_en'] ?? $dua['translation'] ?? null;
                if (isset($dua['transliteration'])) {
                    $merged[$key]['transliteration'] = $dua['transliteration'];
                }
            }
        }

        return $merged;
    }

    private function validateDuaData(array $data): void
    {
        if (empty($data['dua_key'])) {
            throw new \Exception("Dua missing dua_key");
        }

        if (empty($data['text_ar'])) {
            throw new \Exception("Dua {$data['dua_key']} missing Arabic text");
        }
    }
}
