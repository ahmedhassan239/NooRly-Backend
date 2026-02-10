<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\QuranAllLang\Helpers\SurahHelper;
use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Quran Controller
 * 
 * Provides public API endpoints for Quran content.
 * Uses the quran_all_lang database for multi-language support.
 */
class QuranController extends Controller
{
    /**
     * List all surahs
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function surahs(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        
        $surahs = collect(SurahHelper::getSurahNames())->map(function ($name, $number) use ($locale) {
            $arabicName = SurahHelper::getArabicSurahName((int) $number);
            $verseCount = QuranVerse::where('surah_number', $number)->count();
            
            return [
                'number' => (int) $number,
                'name' => $locale === 'ar' ? $arabicName : $name,
                'name_ar' => $arabicName,
                'name_en' => $name,
                'verse_count' => $verseCount,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Surahs retrieved successfully',
            'data' => $surahs,
        ]);
    }

    /**
     * Get a surah with its verses
     * 
     * @param Request $request
     * @param int $surahNumber
     * @return JsonResponse
     */
    public function surah(Request $request, int $surahNumber): JsonResponse
    {
        if ($surahNumber < 1 || $surahNumber > 114) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid surah number',
            ], 400);
        }

        $locale = $request->header('Accept-Language', 'en');
        $translationId = $request->input('translation_id');
        
        $query = QuranVerse::where('surah_number', $surahNumber)
            ->orderBy('ayah_number');

        // Get verses with their texts
        $query->with(['verseTexts' => function ($q) use ($translationId) {
            $q->forActiveLanguages();
            if ($translationId) {
                $q->where('translation_id', $translationId);
            }
            $q->with('translation.language');
        }]);

        $perPage = min((int) $request->input('per_page', 50), 100);
        $verses = $query->paginate($perPage);

        $surahName = SurahHelper::getSurahNames()[$surahNumber] ?? "Surah {$surahNumber}";
        $arabicName = SurahHelper::getArabicSurahName($surahNumber);

        return response()->json([
            'status' => true,
            'message' => 'Surah retrieved successfully',
            'data' => [
                'surah' => [
                    'number' => $surahNumber,
                    'name' => $locale === 'ar' ? $arabicName : $surahName,
                    'name_ar' => $arabicName,
                    'name_en' => $surahName,
                    'total_verses' => QuranVerse::where('surah_number', $surahNumber)->count(),
                ],
                'verses' => $verses->map(fn ($verse) => $this->formatVerse($verse, $locale)),
            ],
            'meta' => [
                'current_page' => $verses->currentPage(),
                'per_page' => $verses->perPage(),
                'total' => $verses->total(),
                'last_page' => $verses->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single verse
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function verse(Request $request, int $id): JsonResponse
    {
        $verse = QuranVerse::with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()
              ->with('translation.language');
        }])->findOrFail($id);

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Verse retrieved successfully',
            'data' => $this->formatVerseDetail($verse, $locale),
        ]);
    }

    /**
     * Get verse by reference (surah:ayah)
     * 
     * @param Request $request
     * @param int $surah
     * @param int $ayah
     * @return JsonResponse
     */
    public function verseByReference(Request $request, int $surah, int $ayah): JsonResponse
    {
        $verse = QuranVerse::where('surah_number', $surah)
            ->where('ayah_number', $ayah)
            ->with(['verseTexts' => function ($q) {
                $q->forActiveLanguages()
                  ->with('translation.language');
            }])
            ->first();

        if (!$verse) {
            return response()->json([
                'status' => false,
                'message' => 'Verse not found',
            ], 404);
        }

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Verse retrieved successfully',
            'data' => $this->formatVerseDetail($verse, $locale),
        ]);
    }

    /**
     * Get available languages
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function languages(Request $request): JsonResponse
    {
        $languages = Language::active()
            ->withCount('translations')
            ->orderBy('name')
            ->get()
            ->map(fn ($lang) => [
                'id' => $lang->id,
                'code' => $lang->code,
                'name' => $lang->name,
                'is_rtl' => $lang->is_rtl,
                'translations_count' => $lang->translations_count,
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Languages retrieved successfully',
            'data' => $languages,
        ]);
    }

    /**
     * Search verses (using normalized text for Arabic)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $searchTerm = $request->input('q');
        $locale = $request->header('Accept-Language', 'en');
        $surah = $request->input('surah');

        $query = QuranVerse::query();

        // Filter by surah if provided
        if ($surah) {
            $query->where('surah_number', $surah);
        }

        // Search in verse texts
        $query->whereHas('verseTexts', function ($q) use ($searchTerm) {
            $q->forActiveLanguages();
            
            // Use normalized search for Arabic
            if ($this->isArabic($searchTerm)) {
                $q->searchNormalized($searchTerm);
            } else {
                $q->searchText($searchTerm);
            }
        });

        // Load verse texts
        $query->with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()
              ->with('translation.language');
        }]);

        $query->orderBy('surah_number')->orderBy('ayah_number');

        $perPage = min((int) $request->input('per_page', 15), 50);
        $verses = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Search results retrieved successfully',
            'data' => $verses->map(fn ($verse) => $this->formatVerse($verse, $locale)),
            'meta' => [
                'current_page' => $verses->currentPage(),
                'per_page' => $verses->perPage(),
                'total' => $verses->total(),
                'last_page' => $verses->lastPage(),
                'query' => $searchTerm,
            ],
        ]);
    }

    /**
     * Get daily verse (verse of the day)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function daily(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        
        // Use day of year to get a consistent daily verse
        $dayOfYear = now()->dayOfYear;
        $totalVerses = QuranVerse::count();
        
        // Get a verse based on day of year (cycles through all verses)
        $verseIndex = $dayOfYear % $totalVerses;
        
        $verse = QuranVerse::with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()
              ->with('translation.language');
        }])
        ->orderBy('id')
        ->skip($verseIndex)
        ->first();

        if (!$verse) {
            $verse = QuranVerse::with(['verseTexts' => function ($q) {
                $q->forActiveLanguages()
                  ->with('translation.language');
            }])->first();
        }

        return response()->json([
            'status' => true,
            'message' => 'Daily verse retrieved successfully',
            'data' => $this->formatVerseDetail($verse, $locale),
        ]);
    }

    /**
     * Format verse for list response
     */
    private function formatVerse(QuranVerse $verse, string $locale): array
    {
        $texts = $verse->verseTexts->sortBy(function ($vt) {
            $code = $vt->translation->language->code ?? '';
            return match($code) {
                'en' => 1,
                'ar' => 2,
                default => 3,
            };
        });

        $primaryText = $texts->first();
        $arabicText = $texts->firstWhere(fn ($vt) => $vt->translation->language->code === 'ar');

        return [
            'id' => $verse->id,
            'surah_number' => $verse->surah_number,
            'ayah_number' => $verse->ayah_number,
            'ayah_key' => $verse->ayah_key,
            'text' => $primaryText?->text,
            'text_ar' => $arabicText?->text,
            'surah_name' => $verse->surah_name,
        ];
    }

    /**
     * Format verse for detail response
     */
    private function formatVerseDetail(QuranVerse $verse, string $locale): array
    {
        $data = $this->formatVerse($verse, $locale);
        
        // Group translations by language
        $translations = $verse->verseTexts
            ->sortBy(function ($vt) {
                $code = $vt->translation->language->code ?? '';
                return match($code) {
                    'en' => 1,
                    'ar' => 2,
                    default => 3,
                };
            })
            ->groupBy(fn ($vt) => $vt->translation->language->name)
            ->map(function ($group) {
                return $group->map(fn ($vt) => [
                    'translator' => $vt->translation->source_name,
                    'text' => $vt->text,
                    'direction' => $vt->translation->language->direction,
                ]);
            });

        $data['translations'] = $translations;

        return $data;
    }

    /**
     * Check if text contains Arabic characters
     */
    private function isArabic(string $text): bool
    {
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
    }
}
