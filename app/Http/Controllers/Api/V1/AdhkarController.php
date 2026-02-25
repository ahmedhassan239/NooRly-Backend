<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Adhkar\Adhkar;
use App\Domain\ContentScopes\ContentScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adhkar\StoreAdhkarRequest;
use App\Http\Resources\Api\V1\AdhkarResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Adhkar Controller
 *
 * Provides API endpoints for adhkar (remembrances).
 * GET /adhkar?category_id={id} returns items with is_saved for the current user when authenticated.
 */
class AdhkarController extends Controller
{
    private const SCOPE_KEY = 'adhkar';

    /**
     * List adhkar with pagination and filtering.
     * GET /api/v1/adhkar?category_id={id} — list by category (category must belong to scope adhkar).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Adhkar::query()
            ->active()
            ->ordered();

        if ($request->has('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $scope = ContentScope::where('key', self::SCOPE_KEY)->first();
            if (!$scope) {
                return response()->json([
                    'status' => false,
                    'message' => 'Scope adhkar is not configured',
                ], 422);
            }
            $category = \App\Domain\Categories\Models\Category::where('id', $categoryId)->where('scope_id', $scope->id)->first();
            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'The selected category is invalid or does not belong to scope adhkar.',
                ], 422);
            }
            $query->where('category_id', $categoryId);
        }

        // Filter by category key (legacy)
        if ($request->has('category')) {
            $query->byCategoryKey($request->input('category'));
        }

        if ($request->boolean('featured')) {
            $query->featured();
        }

        if ($request->has('q') && $request->input('q')) {
            $searchTerm = $request->input('q');
            $query->where(function ($q) use ($searchTerm) {
                $q->whereRaw("JSON_EXTRACT(text, '$.en') LIKE ?", ["%{$searchTerm}%"])
                  ->orWhereRaw("JSON_EXTRACT(text, '$.ar') LIKE ?", ["%{$searchTerm}%"]);
                $normalizedTerm = \App\Support\Arabic\ArabicTextNormalizer::normalize($searchTerm);
                $q->orWhere('text_ar_normalized', 'like', "%{$normalizedTerm}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 50);
        $adhkar = $query->with('category')->paginate($perPage);

        $savedIds = $this->savedAdhkarIds($request);
        $data = $adhkar->map(fn ($dhikr) => new AdhkarResource([
            'adhkar' => $dhikr,
            'is_saved' => $savedIds->contains((string) $dhikr->id),
        ]))->map(fn ($r) => $r->toArray($request));

        return response()->json([
            'status' => true,
            'message' => 'Adhkar retrieved successfully',
            'data' => $data,
            'meta' => [
                'current_page' => $adhkar->currentPage(),
                'per_page' => $adhkar->perPage(),
                'total' => $adhkar->total(),
                'last_page' => $adhkar->lastPage(),
            ],
        ]);
    }

    /**
     * Create adhkar. Accepts only category_id (required, must belong to adhkar scope)
     * plus optional text, reward, count, source. Legacy category fields are rejected.
     */
    public function store(StoreAdhkarRequest $request): JsonResponse
    {
        $data = $request->validated();
        $text = $data['text'] ?? [];
        $adhkar = Adhkar::create([
            'category_id' => $data['category_id'],
            'text' => [
                'ar' => $text['ar'] ?? '',
                'en' => $text['en'] ?? '',
            ],
            'reward' => $data['reward'] ?? null,
            'count' => $data['count'] ?? 1,
            'source' => $data['source'] ?? null,
            'is_active' => true,
        ]);
        $adhkar->load('category');
        $locale = $request->header('Accept-Language', 'en');
        return response()->json([
            'status' => true,
            'message' => 'Adhkar created successfully',
            'data' => $adhkar->toApiArray($locale),
        ], 201);
    }

    /**
     * Get a single dhikr. GET /api/v1/adhkar/{id}. Includes is_saved when authenticated.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $dhikr = Adhkar::with('category')
            ->active()
            ->findOrFail($id);

        $savedIds = $this->savedAdhkarIds($request);
        $resource = new AdhkarResource([
            'adhkar' => $dhikr,
            'is_saved' => $savedIds->contains((string) $dhikr->id),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Dhikr retrieved successfully',
            'data' => $resource->toArray($request),
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
     * Get adhkar by category key (legacy: morning, evening, etc.)
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

    /**
     * Get adhkar by admin category ID (Library scope=Adhkar).
     * GET /api/v1/adhkar/by-category/{id}. Returns items with is_saved when authenticated.
     * 422 if category does not belong to scope adhkar.
     */
    public function byCategoryId(Request $request, int $id): JsonResponse
    {
        $scope = ContentScope::where('key', self::SCOPE_KEY)->first();
        if (!$scope) {
            return response()->json(['status' => false, 'message' => 'Scope adhkar is not configured'], 422);
        }
        $category = \App\Domain\Categories\Models\Category::where('id', $id)->where('scope_id', $scope->id)->first();
        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'The selected category is invalid or does not belong to scope adhkar.',
            ], 422);
        }

        $adhkar = Adhkar::active()
            ->where('category_id', $id)
            ->ordered()
            ->with('category')
            ->get();

        $savedIds = $this->savedAdhkarIds($request);
        $data = $adhkar->map(fn ($dhikr) => (new AdhkarResource([
            'adhkar' => $dhikr,
            'is_saved' => $savedIds->contains((string) $dhikr->id),
        ]))->toArray($request));

        return response()->json([
            'status' => true,
            'message' => 'Adhkar retrieved successfully',
            'data' => $data,
        ]);
    }

    /** Return set of saved adhkar item ids for the current user (empty when not authenticated). */
    private function savedAdhkarIds(Request $request): \Illuminate\Support\Collection
    {
        $user = $request->user();
        if (!$user) {
            return collect();
        }
        return $user->savedItems()
            ->where('item_type', 'adhkar')
            ->pluck('item_id');
    }
}
