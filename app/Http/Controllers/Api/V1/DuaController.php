<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Categories\Models\Category;
use App\Domain\Duas\Dua;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DuaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dua Controller
 * 
 * Provides API endpoints for duas (supplications).
 */
class DuaController extends Controller
{
    /**
     * List duas with pagination and filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Dua::query()
            ->where('is_active', true)
            ->orderBy('position');

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->input('category_id'));
            });
        }

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->where('is_featured', true);
        }

        // Search
        if ($request->has('q') && $request->input('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                // Search in translations
                $q->whereHas('translations', function ($tq) use ($searchTerm) {
                    $tq->where('name', 'like', "%{$searchTerm}%")
                       ->orWhere('text', 'like', "%{$searchTerm}%");
                });
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $duas = $query->with(['translations', 'categories'])->paginate($perPage);

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Duas retrieved successfully',
            'data' => $duas->map(fn ($dua) => $this->formatDua($dua, $locale)),
            'meta' => [
                'current_page' => $duas->currentPage(),
                'per_page' => $duas->perPage(),
                'total' => $duas->total(),
                'last_page' => $duas->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single dua
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dua = Dua::with(['translations', 'categories', 'quranAyahs', 'hadithItems'])
            ->where('is_active', true)
            ->findOrFail($id);

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Dua retrieved successfully',
            'data' => $this->formatDuaDetail($dua, $locale),
        ]);
    }

    /**
     * Get dua categories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function categories(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        
        // Get categories that have the 'duas' scope
        $categories = Category::whereHas('scope', function ($q) {
            $q->where('key', 'duas');
        })
        ->with('translations')
        ->get()
        ->map(function ($category) use ($locale) {
            // Count duas attached to this category via categorizables pivot
            $duasCount = \DB::table('categorizables')
                ->where('category_id', $category->id)
                ->where('categorizable_type', Dua::class)
                ->count();
            
            return [
                'id' => $category->id,
                'name' => $category->getName($locale),
                'slug' => $category->getSlug($locale),
                'description' => $category->getDescription($locale),
                'duas_count' => $duasCount,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Dua categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    /**
     * Search duas
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

        $query = Dua::query()
            ->where('is_active', true);

        // Search in translations
        $query->whereHas('translations', function ($tq) use ($searchTerm) {
            $tq->where('name', 'like', "%{$searchTerm}%")
               ->orWhere('text', 'like', "%{$searchTerm}%");
        });

        // Also search in normalized Arabic text if available
        if ($this->isArabic($searchTerm)) {
            $normalizedTerm = \App\Support\Arabic\ArabicTextNormalizer::normalize($searchTerm);
            $query->orWhere('text_ar_normalized', 'like', "%{$normalizedTerm}%");
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $duas = $query->with('translations')->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Search results retrieved successfully',
            'data' => $duas->map(fn ($dua) => $this->formatDua($dua, $locale)),
            'meta' => [
                'current_page' => $duas->currentPage(),
                'per_page' => $duas->perPage(),
                'total' => $duas->total(),
                'last_page' => $duas->lastPage(),
                'query' => $searchTerm,
            ],
        ]);
    }

    /**
     * Format dua for list response
     */
    private function formatDua(Dua $dua, string $locale): array
    {
        return [
            'id' => $dua->id,
            'name' => $dua->getTranslation('name', $locale),
            'text' => $dua->getTranslation('text', $locale),
            'text_ar' => $dua->getTranslation('text', 'ar'),
            'transliteration' => $dua->transliteration,
            'source' => $dua->source,
            'is_featured' => $dua->is_featured,
            'categories' => $dua->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->getTranslation('name', $locale),
            ]),
        ];
    }

    /**
     * Format dua for detail response
     */
    private function formatDuaDetail(Dua $dua, string $locale): array
    {
        $data = $this->formatDua($dua, $locale);
        
        // Add related content
        $data['quran_references'] = $dua->quranAyahs->map(fn ($verse) => [
            'id' => $verse->id,
            'surah_number' => $verse->surah_number,
            'ayah_number' => $verse->ayah_number,
            'ayah_key' => $verse->ayah_key,
        ]);
        
        $data['hadith_references'] = $dua->hadithItems->map(fn ($hadith) => [
            'id' => $hadith->id,
            'source' => $hadith->source ?? $hadith->collection,
            'number' => $hadith->hadith_no ?? $hadith->number,
        ]);

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
