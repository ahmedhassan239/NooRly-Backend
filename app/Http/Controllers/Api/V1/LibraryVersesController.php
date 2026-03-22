<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\ContentScopes\ContentScope;
use App\Domain\QuranAllLang\Helpers\SurahHelper;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\Verses\VerseCollection;
use App\Http\Controllers\Controller;
use App\Support\Icons\PublicIconsRegistry;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Library Verses: categories (by scope verses) → collections per category → collection detail with verses.
 * All data from DB; no mock.
 */
class LibraryVersesController extends Controller
{
    use ApiResponseTrait;

    /**
     * Parse preferred locale from Accept-Language header (e.g. "ar-EG,ar;q=0.9,en;q=0.8" -> "ar").
     */
    private function getLocale(Request $request): string
    {
        $header = $request->header('Accept-Language', 'en');
        $first = trim(explode(',', $header)[0] ?? 'en');
        $lang = trim(explode(';', $first)[0] ?? 'en');
        $locale = strlen($lang) >= 2 ? strtolower(substr($lang, 0, 2)) : 'en';
        return in_array($locale, ['en', 'ar'], true) ? $locale : 'en';
    }

    /**
     * GET /api/v1/library/verses/categories
     */
    public function categories(Request $request): JsonResponse
    {
        $scope = ContentScope::where('key', 'verses')->first();
        if (!$scope) {
            return $this->successResponse([], 'No verses scope configured');
        }

        $categories = \App\Domain\Categories\Models\Category::query()
            ->with(['translations'])
            ->where('scope_id', $scope->id)
            ->get()
            ->map(function ($category) {
                return array_merge([
                    'id' => $category->id,
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'description' => $category->getDescription(),
                ], PublicIconsRegistry::expand($category->icon_key));
            });

        return $this->successResponse($categories->toArray(), 'Categories retrieved successfully');
    }

    /**
     * GET /api/v1/library/verses/categories/{id}/collections
     * Title/description/slug localized via Accept-Language.
     */
    public function collectionsByCategory(Request $request, int $id): JsonResponse
    {
        $locale = $this->getLocale($request);
        $category = \App\Domain\Categories\Models\Category::find($id);
        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        $collectionIds = DB::table('category_verse_coll')
            ->where('category_id', $id)
            ->pluck('verse_collection_id');

        $collections = VerseCollection::query()
            ->with('translations')
            ->whereIn('id', $collectionIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerseCollection $c) => array_merge([
                'id' => $c->id,
                'title' => $c->getTitle($locale),
                'slug' => $c->getSlug($locale),
                'description' => $c->getDescription($locale),
                'display_order' => $c->display_order,
            ], PublicIconsRegistry::expand($c->icon)));

        return $this->successResponse($collections->toArray(), 'Collections retrieved successfully');
    }

    /**
     * GET /api/v1/library/verses/collections
     * All verse collections (no category). Sorted by display_order then id. Includes items_count.
     */
    public function collections(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);
        $collections = VerseCollection::query()
            ->with('translations')
            ->selectRaw(
                'verse_collections.id, verse_collections.title, verse_collections.slug, ' .
                'verse_collections.icon, verse_collections.display_order, ' .
                '(SELECT COUNT(*) FROM verse_collection_ayah WHERE verse_collection_ayah.verse_collection_id = verse_collections.id) as items_count'
            )
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (VerseCollection $c) => array_merge([
                'id' => $c->id,
                'title' => $c->getTitle($locale),
                'slug' => $c->getSlug($locale),
                'description' => $c->getDescription($locale),
                'display_order' => (int) $c->display_order,
                'items_count' => (int) ($c->items_count ?? 0),
            ], PublicIconsRegistry::expand($c->icon)));

        return $this->successResponse($collections->toArray(), 'Collections retrieved successfully');
    }

    /**
     * GET /api/v1/library/verses/collections/{id}
     * Collection with verses (Arabic, translation, reference). Collection title/slug localized via Accept-Language.
     */
    public function collection(Request $request, int $id): JsonResponse
    {
        $collection = VerseCollection::with('translations')->find($id);
        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $locale = $this->getLocale($request);
        $ayahIds = $collection->getQuranAyahIds();
        if (empty($ayahIds)) {
            return $this->successResponse([
                'collection' => array_merge([
                    'id' => $collection->id,
                    'title' => $collection->getTitle($locale),
                    'slug' => $collection->getSlug($locale),
                    'description' => $collection->getDescription($locale),
                ], PublicIconsRegistry::expand($collection->icon)),
                'verses' => [],
            ], 'Collection retrieved successfully');
        }

        // QuranVerse is in quran_all_lang; id may match stored quran_ayah_id
        $verses = QuranVerse::whereIn('id', $ayahIds)
            ->with(['verseTexts' => fn ($q) => $q->forActiveLanguages()->with('translation.language')])
            ->get()
            ->keyBy('id');

        $ordered = [];
        foreach ($ayahIds as $vid) {
            $verse = $verses->get($vid);
            if (!$verse) {
                continue;
            }
            $textAr = null;
            $textEn = null;
            foreach ($verse->verseTexts ?? [] as $vt) {
                $code = $vt->translation->language->code ?? '';
                if ($code === 'ar') {
                    $textAr = $vt->text ?? $vt->text_normalized ?? null;
                }
                if ($code === 'en' || $code === 'eng') {
                    $textEn = $vt->text ?? $vt->text_normalized ?? null;
                }
            }
            $ordered[] = [
                'id' => $verse->id,
                'surah_number' => $verse->surah_number,
                'ayah_number' => $verse->ayah_number,
                'ayah_key' => $verse->ayah_key ?? "{$verse->surah_number}:{$verse->ayah_number}",
                'reference' => "{$verse->surah_number}:{$verse->ayah_number}",
                'surah_name_en' => SurahHelper::getName($verse->surah_number),
                'surah_name_ar' => SurahHelper::getArabicSurahName($verse->surah_number),
                'text_ar' => $textAr,
                'text_en' => $textEn,
                'text' => $locale === 'ar' ? $textAr : $textEn,
            ];
        }

        return $this->successResponse([
            'collection' => array_merge([
                'id' => $collection->id,
                'title' => $collection->getTitle($locale),
                'slug' => $collection->getSlug($locale),
                'description' => $collection->getDescription($locale),
            ], PublicIconsRegistry::expand($collection->icon)),
            'verses' => $ordered,
        ], 'Collection retrieved successfully');
    }
}
