<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\QuranAllLang\Models\Translation;
use App\Domain\QuranAllLang\Models\VerseText;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuranAllLang\StoreLanguageRequest;
use App\Http\Requests\QuranAllLang\StoreTranslationRequest;
use App\Http\Requests\QuranAllLang\UpdateLanguageRequest;
use App\Http\Requests\QuranAllLang\UpdateTranslationRequest;
use App\Http\Requests\QuranAllLang\UpdateVerseTextRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuranAllLangController extends Controller
{
    /**
     * Get all languages
     */
    public function indexLanguages(Request $request): JsonResponse
    {
        $query = Language::query();

        // Filter by RTL
        if ($request->has('rtl')) {
            $rtl = filter_var($request->input('rtl'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_rtl', $rtl);
        }

        // Include counts
        if ($request->boolean('with_counts')) {
            $query->withCount('translations');
        }

        $languages = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $languages,
        ]);
    }

    /**
     * Get single language
     */
    public function showLanguage(int $id): JsonResponse
    {
        $language = Language::withCount('translations')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $language,
        ]);
    }

    /**
     * Create language
     */
    public function storeLanguage(StoreLanguageRequest $request): JsonResponse
    {
        $language = Language::create($request->validated());

        return response()->json([
            'success' => true,
            'data' => $language,
            'message' => 'Language created successfully.',
        ], 201);
    }

    /**
     * Update language
     */
    public function updateLanguage(UpdateLanguageRequest $request, int $id): JsonResponse
    {
        $language = Language::findOrFail($id);
        $language->update($request->validated());

        return response()->json([
            'success' => true,
            'data' => $language->fresh(),
            'message' => 'Language updated successfully.',
        ]);
    }

    /**
     * Delete language
     */
    public function destroyLanguage(int $id): JsonResponse
    {
        $language = Language::findOrFail($id);
        $language->delete();

        return response()->json([
            'success' => true,
            'message' => 'Language deleted successfully.',
        ]);
    }

    /**
     * Get all translations
     */
    public function indexTranslations(Request $request): JsonResponse
    {
        $query = Translation::with('language');

        // Filter by language
        if ($request->has('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        // Include counts
        if ($request->boolean('with_counts')) {
            $query->withCount('verseTexts');
        }

        $perPage = min((int) $request->input('per_page', 25), 100);
        $translations = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $translations->items(),
            'meta' => [
                'current_page' => $translations->currentPage(),
                'per_page' => $translations->perPage(),
                'total' => $translations->total(),
                'last_page' => $translations->lastPage(),
            ],
        ]);
    }

    /**
     * Get single translation
     */
    public function showTranslation(int $id): JsonResponse
    {
        $translation = Translation::with('language')
            ->withCount('verseTexts')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $translation,
        ]);
    }

    /**
     * Create translation
     */
    public function storeTranslation(StoreTranslationRequest $request): JsonResponse
    {
        $translation = Translation::create($request->validated());
        $translation->load('language');

        return response()->json([
            'success' => true,
            'data' => $translation,
            'message' => 'Translation created successfully.',
        ], 201);
    }

    /**
     * Update translation
     */
    public function updateTranslation(UpdateTranslationRequest $request, int $id): JsonResponse
    {
        $translation = Translation::findOrFail($id);
        $translation->update($request->validated());
        $translation->load('language');

        return response()->json([
            'success' => true,
            'data' => $translation,
            'message' => 'Translation updated successfully.',
        ]);
    }

    /**
     * Delete translation
     */
    public function destroyTranslation(int $id): JsonResponse
    {
        $translation = Translation::findOrFail($id);
        $translation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Translation deleted successfully.',
        ]);
    }

    /**
     * Get all verses with filtering and pagination
     */
    public function indexVerses(Request $request): JsonResponse
    {
        $query = QuranVerse::query();

        // Filter by surah
        if ($request->has('surah')) {
            $query->where('surah_number', $request->input('surah'));
        }

        // Filter by ayah
        if ($request->has('ayah')) {
            $query->where('ayah_number', $request->input('ayah'));
        }

        // Filter by language (through verse texts)
        if ($request->has('language')) {
            $query->whereHas('verseTexts.translation', function ($q) use ($request) {
                $q->where('language_id', $request->input('language'));
            });
        }

        // Filter by translation
        if ($request->has('translation')) {
            $query->whereHas('verseTexts', function ($q) use ($request) {
                $q->where('translation_id', $request->input('translation'));
            });
        }

        // Search in text
        if ($request->has('q') && $request->input('q')) {
            $query->searchText($request->input('q'));
        }

        // Include verse texts count
        $query->withCount('verseTexts');

        // Ordering
        $query->orderBy('surah_number')->orderBy('ayah_number');

        $perPage = min((int) $request->input('per_page', 25), 100);
        $verses = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $verses->items(),
            'meta' => [
                'current_page' => $verses->currentPage(),
                'per_page' => $verses->perPage(),
                'total' => $verses->total(),
                'last_page' => $verses->lastPage(),
            ],
        ]);
    }

    /**
     * Get single verse with all translations
     */
    public function showVerse(Request $request, int $id): JsonResponse
    {
        $verse = QuranVerse::with(['verseTexts' => function ($query) use ($request) {
            $query->with('translation.language');
            
            // Filter by language if specified
            if ($request->has('language')) {
                $query->whereHas('translation', function ($q) use ($request) {
                    $q->where('language_id', $request->input('language'));
                });
            }
            
            // Filter by translation if specified
            if ($request->has('translation')) {
                $query->where('translation_id', $request->input('translation'));
            }
        }])->findOrFail($id);

        // Group translations by language
        $grouped = $verse->verseTexts->groupBy(function ($verseText) {
            return $verseText->translation->language->name;
        })->map(function ($group) {
            return $group->map(function ($verseText) {
                return [
                    'id' => $verseText->id,
                    'translation_id' => $verseText->translation_id,
                    'translator' => $verseText->translation->source_name,
                    'text' => $verseText->text,
                    'direction' => $verseText->translation->language->direction,
                    'updated_at' => $verseText->updated_at,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $verse->id,
                'surah_number' => $verse->surah_number,
                'ayah_number' => $verse->ayah_number,
                'ayah_key' => $verse->ayah_key,
                'full_reference' => $verse->full_reference,
                'translations' => $grouped,
                'created_at' => $verse->created_at,
            ],
        ]);
    }

    /**
     * Update verse text
     */
    public function updateVerseText(UpdateVerseTextRequest $request, int $id): JsonResponse
    {
        $verseText = VerseText::findOrFail($id);
        $verseText->update($request->validated());
        $verseText->load('translation.language', 'verse');

        return response()->json([
            'success' => true,
            'data' => $verseText,
            'message' => 'Verse text updated successfully.',
        ]);
    }
}
