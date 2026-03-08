<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\ContentScopes\ContentScope;
use App\Domain\Hadith\HadithCollection;
use App\Domain\Hadith\Models\HadithItem;
use App\Http\Controllers\Controller;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Library Hadith: categories (by scope hadith) → collections per category → collection detail with hadiths.
 * All data from DB; no mock.
 */
class LibraryHadithController extends Controller
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

    private function getHadithConfig(): array
    {
        return [
            'connection' => config('content_sources.hadith.connection', 'mysql_hadith'),
            'table' => config('content_sources.hadith.table', 'all_hadiths_clean.hadiths'),
            'columns' => config('content_sources.hadith.columns', [
                'collection' => 'source',
                'book_number' => 'chapter_no',
                'hadith_number' => 'hadith_no',
                'text_ar' => 'text_ar',
                'text_en' => 'text_en',
            ]),
        ];
    }

    /**
     * GET /api/v1/library/hadith/categories
     * Categories for hadith scope (for Library Hadith tab).
     */
    public function categories(Request $request): JsonResponse
    {
        $scope = ContentScope::where('key', 'hadith')->first();
        if (!$scope) {
            return $this->successResponse([], 'No hadith scope configured');
        }

        $categories = \App\Domain\Categories\Models\Category::query()
            ->with(['translations'])
            ->where('scope_id', $scope->id)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->getName(),
                    'slug' => $category->getSlug(),
                    'description' => $category->getDescription(),
                ];
            });

        return $this->successResponse($categories->toArray(), 'Categories retrieved successfully');
    }

    /**
     * GET /api/v1/library/hadith/collections
     * All hadith collections (no category). Sorted by display_order then id.
     * Includes items_count. Title/description/slug localized via Accept-Language.
     */
    public function collections(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);
        $collections = HadithCollection::query()
            ->with('translations')
            ->selectRaw(
                'library_hadith_collections.id, library_hadith_collections.title, library_hadith_collections.slug, ' .
                'library_hadith_collections.icon, library_hadith_collections.display_order, ' .
                '(SELECT COUNT(*) FROM lib_hadith_collection_item WHERE lib_hadith_collection_item.hadith_collection_id = library_hadith_collections.id) as items_count'
            )
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HadithCollection $c) => [
                'id' => $c->id,
                'title' => $c->getTitle($locale),
                'slug' => $c->getSlug($locale),
                'description' => $c->getDescription($locale),
                'display_order' => (int) $c->display_order,
                'items_count' => (int) $c->items_count,
                'icon' => filled($c->icon) ? $c->icon : null,
            ]);

        return $this->successResponse($collections->toArray(), 'Collections retrieved successfully');
    }

    /**
     * GET /api/v1/library/hadith/categories/{id}/collections
     * Hadith collections linked to this category. Title/description/slug localized via Accept-Language.
     */
    public function collectionsByCategory(Request $request, int $id): JsonResponse
    {
        $locale = $this->getLocale($request);
        $category = \App\Domain\Categories\Models\Category::find($id);
        if (!$category) {
            return $this->errorResponse('Category not found', 404);
        }

        $collectionIds = DB::table('category_lib_hadith_collection')
            ->where('category_id', $id)
            ->pluck('hadith_collection_id');

        $collections = HadithCollection::query()
            ->with('translations')
            ->whereIn('id', $collectionIds)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HadithCollection $c) => [
                'id' => $c->id,
                'title' => $c->getTitle($locale),
                'slug' => $c->getSlug($locale),
                'description' => $c->getDescription($locale),
                'display_order' => $c->display_order,
                'icon' => filled($c->icon) ? $c->icon : null,
            ]);

        return $this->successResponse($collections->toArray(), 'Collections retrieved successfully');
    }

    /**
     * GET /api/v1/library/hadith/collections/{id}
     * Single collection with hadith items (text, source, reference). Collection title/slug localized via Accept-Language.
     */
    public function collection(Request $request, int $id): JsonResponse
    {
        $collection = HadithCollection::with('translations')->find($id);
        if (!$collection) {
            return $this->errorResponse('Collection not found', 404);
        }

        $config = $this->getHadithConfig();
        $cols = $config['columns'];
        $locale = $this->getLocale($request);

        $hadithIds = $collection->getHadithItemIds();
        if (empty($hadithIds)) {
            return $this->successResponse([
                'collection' => [
                    'id' => $collection->id,
                    'title' => $collection->getTitle($locale),
                    'slug' => $collection->getSlug($locale),
                    'description' => $collection->getDescription($locale),
                    'icon' => filled($collection->icon) ? $collection->icon : null,
                ],
                'hadiths' => [],
            ], 'Collection retrieved successfully');
        }

        $rows = DB::connection($config['connection'])
            ->table($config['table'])
            ->whereIn('id', $hadithIds)
            ->get()
            ->keyBy('id');

        $hadiths = [];
        foreach ($hadithIds as $hid) {
            $row = $rows->get($hid);
            if (!$row) {
                continue;
            }
            $hadiths[] = [
                'id' => $row->id,
                'collection' => $row->{$cols['collection']},
                'collection_name' => $this->formatCollectionName($row->{$cols['collection']}),
                'hadith_number' => $row->{$cols['hadith_number']},
                'chapter_number' => $row->{$cols['book_number']} ?? null,
                'text_ar' => $row->{$cols['text_ar']} ?? null,
                'text_en' => $row->{$cols['text_en']} ?? null,
                'text' => $locale === 'ar' ? ($row->{$cols['text_ar']} ?? null) : ($row->{$cols['text_en']} ?? null),
            ];
        }

        return $this->successResponse([
            'collection' => [
                'id' => $collection->id,
                'title' => $collection->getTitle($locale),
                'slug' => $collection->getSlug($locale),
                'description' => $collection->getDescription($locale),
                'icon' => filled($collection->icon) ? $collection->icon : null,
            ],
            'hadiths' => $hadiths,
        ], 'Collection retrieved successfully');
    }

    private function formatCollectionName(string $source): string
    {
        $names = [
            'bukhari' => 'Sahih al-Bukhari',
            'muslim' => 'Sahih Muslim',
            'tirmidhi' => 'Jami` at-Tirmidhi',
            'abudawud' => 'Sunan Abu Dawud',
            'nasai' => 'Sunan an-Nasa\'i',
            'ibnmajah' => 'Sunan Ibn Majah',
            'malik' => 'Muwatta Malik',
            'ahmad' => 'Musnad Ahmad',
        ];
        return $names[strtolower($source)] ?? ucfirst($source);
    }
}
