<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Hadith\Models\HadithItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Hadith Controller
 * 
 * Provides public API endpoints for Hadith content.
 * Uses env-driven database connection for hadith data.
 */
class HadithController extends Controller
{
    /**
     * Get hadith connection and table from config
     */
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

    /**
     * List hadith collections/sources
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function collections(Request $request): JsonResponse
    {
        $config = $this->getHadithConfig();
        $collectionCol = $config['columns']['collection'];
        
        $collections = DB::connection($config['connection'])
            ->table($config['table'])
            ->select($collectionCol)
            ->selectRaw("COUNT(*) as hadith_count")
            ->groupBy($collectionCol)
            ->orderBy($collectionCol)
            ->get()
            ->map(function ($item) use ($collectionCol) {
                $source = $item->{$collectionCol};
                return [
                    'key' => $source,
                    'name' => $this->formatCollectionName($source),
                    'hadith_count' => $item->hadith_count,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Hadith collections retrieved successfully',
            'data' => $collections,
        ]);
    }

    /**
     * Get hadiths from a collection
     * 
     * @param Request $request
     * @param string $collection
     * @return JsonResponse
     */
    public function collection(Request $request, string $collection): JsonResponse
    {
        $config = $this->getHadithConfig();
        $cols = $config['columns'];
        $locale = $request->header('Accept-Language', 'en');
        
        $query = DB::connection($config['connection'])
            ->table($config['table'])
            ->where($cols['collection'], $collection)
            ->orderBy($cols['hadith_number']);

        $perPage = min((int) $request->input('per_page', 15), 50);
        $hadiths = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Hadiths retrieved successfully',
            'data' => [
                'collection' => [
                    'key' => $collection,
                    'name' => $this->formatCollectionName($collection),
                ],
                'hadiths' => collect($hadiths->items())->map(fn ($h) => $this->formatHadith($h, $cols, $locale)),
            ],
            'meta' => [
                'current_page' => $hadiths->currentPage(),
                'per_page' => $hadiths->perPage(),
                'total' => $hadiths->total(),
                'last_page' => $hadiths->lastPage(),
            ],
        ]);
    }

    /**
     * List all hadiths with pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $config = $this->getHadithConfig();
        $cols = $config['columns'];
        $locale = $request->header('Accept-Language', 'en');
        
        $query = DB::connection($config['connection'])
            ->table($config['table']);

        // Filter by collection
        if ($request->has('collection')) {
            $query->where($cols['collection'], $request->input('collection'));
        }

        $query->orderBy($cols['collection'])
              ->orderBy($cols['hadith_number']);

        $perPage = min((int) $request->input('per_page', 15), 50);
        $hadiths = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Hadiths retrieved successfully',
            'data' => collect($hadiths->items())->map(fn ($h) => $this->formatHadith($h, $cols, $locale)),
            'meta' => [
                'current_page' => $hadiths->currentPage(),
                'per_page' => $hadiths->perPage(),
                'total' => $hadiths->total(),
                'last_page' => $hadiths->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single hadith
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $config = $this->getHadithConfig();
        $cols = $config['columns'];
        $locale = $request->header('Accept-Language', 'en');
        
        $hadith = DB::connection($config['connection'])
            ->table($config['table'])
            ->where('id', $id)
            ->first();

        if (!$hadith) {
            return response()->json([
                'status' => false,
                'message' => 'Hadith not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Hadith retrieved successfully',
            'data' => $this->formatHadithDetail($hadith, $cols, $locale),
        ]);
    }

    /**
     * Search hadiths (using normalized text for Arabic)
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
        $config = $this->getHadithConfig();
        $cols = $config['columns'];
        $locale = $request->header('Accept-Language', 'en');
        
        $query = DB::connection($config['connection'])
            ->table($config['table']);

        // Filter by collection if provided
        if ($request->has('collection')) {
            $query->where($cols['collection'], $request->input('collection'));
        }

        // Search in Arabic and English text
        $query->where(function ($q) use ($cols, $searchTerm) {
            $q->where($cols['text_ar'], 'like', "%{$searchTerm}%")
              ->orWhere($cols['text_en'], 'like', "%{$searchTerm}%");
            
            // Also search in normalized column if it exists
            if (isset($cols['text_ar_normalized'])) {
                $normalizedTerm = \App\Support\Arabic\ArabicTextNormalizer::normalize($searchTerm);
                $q->orWhere($cols['text_ar_normalized'], 'like', "%{$normalizedTerm}%");
            }
        });

        $perPage = min((int) $request->input('per_page', 15), 50);
        $hadiths = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Search results retrieved successfully',
            'data' => collect($hadiths->items())->map(fn ($h) => $this->formatHadith($h, $cols, $locale)),
            'meta' => [
                'current_page' => $hadiths->currentPage(),
                'per_page' => $hadiths->perPage(),
                'total' => $hadiths->total(),
                'last_page' => $hadiths->lastPage(),
                'query' => $searchTerm,
            ],
        ]);
    }

    /**
     * Get daily hadith (hadith of the day)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function daily(Request $request): JsonResponse
    {
        $config = $this->getHadithConfig();
        $cols = $config['columns'];
        $locale = $request->header('Accept-Language', 'en');
        
        // Use day of year to get a consistent daily hadith
        $dayOfYear = now()->dayOfYear;
        $totalHadiths = DB::connection($config['connection'])
            ->table($config['table'])
            ->count();
        
        // Get a hadith based on day of year (cycles through all hadiths)
        $hadithIndex = $dayOfYear % $totalHadiths;
        
        $hadith = DB::connection($config['connection'])
            ->table($config['table'])
            ->orderBy('id')
            ->skip($hadithIndex)
            ->first();

        if (!$hadith) {
            $hadith = DB::connection($config['connection'])
                ->table($config['table'])
                ->first();
        }

        return response()->json([
            'status' => true,
            'message' => 'Daily hadith retrieved successfully',
            'data' => $this->formatHadithDetail($hadith, $cols, $locale),
        ]);
    }

    /**
     * Format hadith for list response
     */
    private function formatHadith(object $hadith, array $cols, string $locale): array
    {
        $textCol = $locale === 'ar' ? $cols['text_ar'] : $cols['text_en'];
        
        return [
            'id' => $hadith->id,
            'collection' => $hadith->{$cols['collection']},
            'collection_name' => $this->formatCollectionName($hadith->{$cols['collection']}),
            'hadith_number' => $hadith->{$cols['hadith_number']},
            'chapter_number' => $hadith->{$cols['book_number']} ?? null,
            'text' => $hadith->{$textCol} ?? $hadith->{$cols['text_ar']},
            'text_preview' => \Illuminate\Support\Str::limit($hadith->{$textCol} ?? $hadith->{$cols['text_ar']}, 200),
        ];
    }

    /**
     * Format hadith for detail response
     */
    private function formatHadithDetail(object $hadith, array $cols, string $locale): array
    {
        return [
            'id' => $hadith->id,
            'collection' => $hadith->{$cols['collection']},
            'collection_name' => $this->formatCollectionName($hadith->{$cols['collection']}),
            'hadith_number' => $hadith->{$cols['hadith_number']},
            'chapter_number' => $hadith->{$cols['book_number']} ?? null,
            'text_ar' => $hadith->{$cols['text_ar']},
            'text_en' => $hadith->{$cols['text_en']},
            'text' => $locale === 'ar' ? $hadith->{$cols['text_ar']} : $hadith->{$cols['text_en']},
        ];
    }

    /**
     * Format collection name for display
     */
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
