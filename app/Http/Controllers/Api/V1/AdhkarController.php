<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Adhkar\Adhkar;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Adhkar Controller
 * 
 * Provides API endpoints for adhkar (remembrances).
 */
class AdhkarController extends Controller
{
    /**
     * List adhkar with pagination and filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Adhkar::query()
            ->active()
            ->ordered();

        // Filter by category key
        if ($request->has('category')) {
            $query->byCategoryKey($request->input('category'));
        }

        // Filter by featured
        if ($request->boolean('featured')) {
            $query->featured();
        }

        // Search
        if ($request->has('q') && $request->input('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                // Search in title
                $q->whereRaw("JSON_EXTRACT(title, '$.en') LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_EXTRACT(title, '$.ar') LIKE ?", ["%{$searchTerm}%"]);
                
                // Search in normalized Arabic text
                $normalizedTerm = \App\Support\Arabic\ArabicTextNormalizer::normalize($searchTerm);
                $q->orWhere('text_ar_normalized', 'like', "%{$normalizedTerm}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $adhkar = $query->with('category')->paginate($perPage);

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Adhkar retrieved successfully',
            'data' => $adhkar->map(fn ($dhikr) => $dhikr->toApiArray($locale)),
            'meta' => [
                'current_page' => $adhkar->currentPage(),
                'per_page' => $adhkar->perPage(),
                'total' => $adhkar->total(),
                'last_page' => $adhkar->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single dhikr
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dhikr = Adhkar::with('category')
            ->active()
            ->findOrFail($id);

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Dhikr retrieved successfully',
            'data' => $dhikr->toApiArray($locale),
        ]);
    }

    /**
     * Get adhkar categories
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function categories(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        
        $categories = [
            [
                'key' => 'morning',
                'name' => $locale === 'ar' ? 'أذكار الصباح' : 'Morning Adhkar',
                'icon' => 'sun',
                'count' => Adhkar::active()->byCategoryKey('morning')->count(),
            ],
            [
                'key' => 'evening',
                'name' => $locale === 'ar' ? 'أذكار المساء' : 'Evening Adhkar',
                'icon' => 'moon',
                'count' => Adhkar::active()->byCategoryKey('evening')->count(),
            ],
            [
                'key' => 'sleep',
                'name' => $locale === 'ar' ? 'أذكار النوم' : 'Before Sleep',
                'icon' => 'moon',
                'count' => Adhkar::active()->byCategoryKey('sleep')->count(),
            ],
            [
                'key' => 'wakeup',
                'name' => $locale === 'ar' ? 'أذكار الاستيقاظ' : 'After Waking Up',
                'icon' => 'sun',
                'count' => Adhkar::active()->byCategoryKey('wakeup')->count(),
            ],
            [
                'key' => 'prayer',
                'name' => $locale === 'ar' ? 'أذكار بعد الصلاة' : 'After Prayer',
                'icon' => 'hand-raised',
                'count' => Adhkar::active()->byCategoryKey('prayer')->count(),
            ],
            [
                'key' => 'general',
                'name' => $locale === 'ar' ? 'أذكار عامة' : 'General',
                'icon' => 'book-open',
                'count' => Adhkar::active()->byCategoryKey('general')->count(),
            ],
            [
                'key' => 'travel',
                'name' => $locale === 'ar' ? 'أذكار السفر' : 'Travel',
                'icon' => 'globe',
                'count' => Adhkar::active()->byCategoryKey('travel')->count(),
            ],
            [
                'key' => 'food',
                'name' => $locale === 'ar' ? 'أذكار الطعام' : 'Food & Drink',
                'icon' => 'cake',
                'count' => Adhkar::active()->byCategoryKey('food')->count(),
            ],
            [
                'key' => 'protection',
                'name' => $locale === 'ar' ? 'أذكار الحماية' : 'Protection',
                'icon' => 'shield-check',
                'count' => Adhkar::active()->byCategoryKey('protection')->count(),
            ],
        ];

        // Filter out empty categories
        $categories = array_filter($categories, fn ($cat) => $cat['count'] > 0);

        return response()->json([
            'status' => true,
            'message' => 'Adhkar categories retrieved successfully',
            'data' => array_values($categories),
        ]);
    }

    /**
     * Get adhkar by category key
     * 
     * @param Request $request
     * @param string $category
     * @return JsonResponse
     */
    public function byCategory(Request $request, string $category): JsonResponse
    {
        $adhkar = Adhkar::active()
            ->byCategoryKey($category)
            ->ordered()
            ->with('category')
            ->get();

        $locale = $request->header('Accept-Language', 'en');

        return response()->json([
            'status' => true,
            'message' => 'Adhkar retrieved successfully',
            'data' => $adhkar->map(fn ($dhikr) => $dhikr->toApiArray($locale)),
        ]);
    }
}
