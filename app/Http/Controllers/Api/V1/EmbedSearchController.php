<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\QuranAllLang\Helpers\SurahHelper;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight search for Tiptap embed chips.
 * GET /api/v1/search/hadith?q=...&limit=10
 * GET /api/v1/search/ayah?q=...&limit=10
 */
class EmbedSearchController extends Controller
{
    private function getHadithConfig(): array
    {
        return [
            'connection' => config('content_sources.hadith.connection', 'mysql_hadith'),
            'table' => config('content_sources.hadith.table', 'hadiths'),
            'columns' => config('content_sources.hadith.columns', [
                'collection' => 'source',
                'book_number' => 'chapter_no',
                'hadith_number' => 'hadith_no',
                'text_ar' => 'text_ar',
                'text_en' => 'text_en',
            ]),
        ];
    }

    private function formatCollectionName(string $source): string
    {
        $names = [
            'bukhari' => 'Sahih al-Bukhari',
            'muslim' => 'Sahih Muslim',
            'tirmidhi' => 'Jami` at-Tirmidhi',
            'abudawud' => 'Sunan Abi Dawud',
            'nasai' => 'Sunan an-Nasa\'i',
            'ibnmajah' => 'Sunan Ibn Majah',
            'malik' => 'Muwatta Malik',
            'ahmad' => 'Musnad Ahmad',
        ];

        return $names[strtolower($source)] ?? ucfirst($source);
    }

    /**
     * GET /api/v1/search/hadith?q=...&limit=10
     * Returns: [{ id, label, preview_ar, meta }]
     */
    public function hadith(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 25);
        $config = $this->getHadithConfig();
        $cols = $config['columns'];

        $query = DB::connection($config['connection'])->table($config['table']);

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($cols, $searchTerm) {
                $q->where($cols['text_ar'], 'like', "%{$searchTerm}%")
                    ->orWhere($cols['text_en'], 'like', "%{$searchTerm}%");
                if (isset($cols['text_ar_normalized'])) {
                    $normalizedTerm = \App\Support\Arabic\ArabicTextNormalizer::normalize($searchTerm);
                    $q->orWhere($cols['text_ar_normalized'], 'like', "%{$normalizedTerm}%");
                }
            });
        }

        $rows = $query->orderBy('id')->limit($limit)->get();

        $data = $rows->map(function ($h) use ($cols) {
            $collection = $h->{$cols['collection']};
            $num = $h->{$cols['hadith_number']};
            $label = $this->formatCollectionName($collection).' #'.$num;
            $textAr = $h->{$cols['text_ar']} ?? '';

            return [
                'id' => (int) $h->id,
                'label' => $label,
                'preview_ar' => \Illuminate\Support\Str::limit($textAr, 120),
                'meta' => [
                    'collection' => $collection,
                    'hadith_number' => $num,
                    'chapter_number' => $h->{$cols['book_number']} ?? null,
                ],
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/v1/search/ayah?q=...&limit=10
     * Returns: [{ surah, ayah, label, preview_ar, meta }]
     */
    public function ayah(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 25);
        $query = QuranVerse::query();

        if ($request->filled('q')) {
            $searchTerm = $request->input('q');
            $query->whereHas('verseTexts', function ($q) use ($searchTerm) {
                $q->forActiveLanguages();
                if (preg_match('/[\x{0600}-\x{06FF}]/u', $searchTerm)) {
                    $q->searchNormalized($searchTerm);
                } else {
                    $q->searchText($searchTerm);
                }
            });
        }

        $query->with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()->with('translation.language');
        }]);
        $query->orderBy('surah_number')->orderBy('ayah_number');
        $verses = $query->limit($limit)->get();

        $surahNames = SurahHelper::getSurahNames();

        $data = $verses->map(function ($verse) use ($surahNames) {
            $surah = (int) $verse->surah_number;
            $ayah = (int) $verse->ayah_number;
            $surahName = $surahNames[$surah] ?? "Surah {$surah}";
            $label = "{$surahName} {$surah}:{$ayah}";
            $arabicText = $verse->verseTexts->firstWhere(fn ($vt) => ($vt->translation->language->code ?? '') === 'ar');
            $previewAr = $arabicText?->text ? \Illuminate\Support\Str::limit($arabicText->text, 120) : '';

            return [
                'surah' => $surah,
                'ayah' => $ayah,
                'label' => $label,
                'preview_ar' => $previewAr,
                'meta' => [
                    'surah_name' => $surahName,
                    'ayah_key' => $verse->ayah_key ?? "{$surah}:{$ayah}",
                ],
            ];
        })->values();

        return response()->json(['data' => $data]);
    }
}
