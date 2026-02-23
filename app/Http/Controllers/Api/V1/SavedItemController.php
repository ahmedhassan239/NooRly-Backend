<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\QuranAllLang\Helpers\SurahHelper;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SavedItemResource;
use App\Support\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SavedItemController extends Controller
{
    use ApiResponseTrait;

    /**
     * Supported item types for saving
     */
    private const SUPPORTED_TYPES = ['dua', 'hadith', 'lesson', 'verse', 'adhkar'];

    /**
     * List saved items of a specific type.
     * When type=hadith, returns hydrated hadith content from external DB (saved order, latest first).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['nullable', Rule::in(self::SUPPORTED_TYPES)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $type = $request->query('type');
        if ($type === 'hadith') {
            return $this->savedHadithIndex($request);
        }
        if ($type === 'verse') {
            return $this->savedVersesIndex($request);
        }

        $user = $request->user();
        $query = $user->savedItems();

        if ($type !== null) {
            $query->where('item_type', $type);
        }

        $perPage = (int) $request->query('per_page', 20);
        $items = $query->latest()->paginate($perPage);

        return $this->successResponse(SavedItemResource::collection($items), null, 200, [
            'current_page' => $items->currentPage(),
            'per_page' => $items->perPage(),
            'total' => $items->total(),
            'has_more' => $items->hasMorePages(),
        ]);
    }

    /**
     * Save/Bookmark an item.
     */
    public function store(Request $request, string $type, string $itemId): JsonResponse
    {
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            return $this->errorResponse(
                'Invalid item type. Supported types: ' . implode(', ', self::SUPPORTED_TYPES),
                400
            );
        }

        $user = $request->user();

        $savedItem = $user->savedItems()->updateOrCreate(
            [
                'item_type' => $type,
                'item_id' => $itemId,
            ]
        );

        return $this->successResponse(new SavedItemResource($savedItem), 'Item saved successfully', 201);
    }

    /**
     * Remove a saved item. Idempotent: always returns success even if item was not saved.
     */
    public function destroy(Request $request, string $type, string $itemId): JsonResponse
    {
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            return $this->errorResponse(
                'Invalid item type. Supported types: ' . implode(', ', self::SUPPORTED_TYPES),
                400
            );
        }

        $request->user()
            ->savedItems()
            ->where('item_type', $type)
            ->where('item_id', $itemId)
            ->delete();

        return $this->successResponse(null, 'Item removed from saved successfully');
    }

    /**
     * GET /saved?type=hadith — hydrated saved hadith from external DB (latest first, paginated).
     */
    private function savedHadithIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage < 1 ? 20 : min($perPage, 100);

        $savedQuery = $user->savedItems()
            ->where('item_type', 'hadith')
            ->orderByDesc('created_at');

        $total = $savedQuery->count();
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        $savedRows = $savedQuery->offset($offset)->limit($perPage)->get(['id', 'item_id', 'created_at']);

        $hadithIds = $savedRows->pluck('item_id')->map(function ($id) {
            return is_numeric($id) ? (int) $id : $id;
        })->all();

        if (count($hadithIds) === 0) {
            return $this->successResponse([
                'items' => [],
                'total' => $total,
            ], null, 200, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $offset + count($hadithIds) < $total,
            ]);
        }

        $config = $this->getHadithConfig();
        $connection = $config['connection'];
        $table = $config['table'];
        $cols = $config['columns'];

        $locale = $this->getPreferredLocale($request);
        $textCol = $locale === 'ar' ? ($cols['text_ar'] ?? 'text_ar') : ($cols['text_en'] ?? 'text_en');
        $collectionCol = $cols['collection'] ?? 'source';
        $bookCol = $cols['book_number'] ?? 'chapter_no';
        $hadithNoCol = $cols['hadith_number'] ?? 'hadith_no';

        $rows = DB::connection($connection)
            ->table($table)
            ->whereIn('id', $hadithIds)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($hadithIds as $hid) {
            $row = $rows->get($hid);
            if (!$row) {
                continue;
            }
            $source = $row->{$collectionCol} ?? '';
            $items[] = [
                'id' => $row->id,
                'collection' => $source,
                'collection_name' => $this->formatCollectionName((string) $source),
                'hadith_number' => $row->{$hadithNoCol} ?? null,
                'chapter_number' => $row->{$bookCol} ?? null,
                'text_ar' => $row->{$cols['text_ar'] ?? 'text_ar'} ?? null,
                'text_en' => $row->{$cols['text_en'] ?? 'text_en'} ?? null,
                'text' => $row->{$textCol} ?? null,
                'is_saved' => true,
            ];
        }

        return $this->successResponse([
            'items' => $items,
            'total' => $total,
        ], null, 200, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $offset + $savedRows->count() < $total,
        ]);
    }

    /**
     * Hadith DB config from content_sources (connection, table, columns).
     */
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
     * Prefer Accept-Language (ar/en); default en.
     */
    private function getPreferredLocale(Request $request): string
    {
        $header = $request->header('Accept-Language', 'en');
        $first = trim(explode(',', $header)[0] ?? 'en');
        $lang = trim(explode(';', $first)[0] ?? 'en');
        $locale = strlen($lang) >= 2 ? strtolower(substr($lang, 0, 2)) : 'en';
        return in_array($locale, ['en', 'ar'], true) ? $locale : 'en';
    }

    /**
     * Human-readable collection/source name for hadith.
     */
    private function formatCollectionName(string $source): string
    {
        if ($source === '') {
            return '';
        }
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

    /**
     * GET /saved?type=verse — hydrated saved verses from Quran DB (latest first, paginated).
     */
    private function savedVersesIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage < 1 ? 20 : min($perPage, 100);

        $savedQuery = $user->savedItems()
            ->where('item_type', 'verse')
            ->orderByDesc('created_at');

        $total = $savedQuery->count();
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        $savedRows = $savedQuery->offset($offset)->limit($perPage)->get(['id', 'item_id', 'created_at']);

        $verseIds = $savedRows->pluck('item_id')->map(function ($id) {
            return is_numeric($id) ? (int) $id : $id;
        })->all();

        if (count($verseIds) === 0) {
            return $this->successResponse([
                'items' => [],
                'total' => $total,
            ], null, 200, [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => false,
            ]);
        }

        $locale = $this->getPreferredLocale($request);

        $verses = QuranVerse::whereIn('id', $verseIds)
            ->with(['verseTexts' => fn ($q) => $q->forActiveLanguages()->with('translation.language')])
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($verseIds as $vid) {
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
            $items[] = [
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
                'is_saved' => true,
            ];
        }

        return $this->successResponse([
            'items' => $items,
            'total' => $total,
        ], null, 200, [
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'has_more' => $offset + $savedRows->count() < $total,
        ]);
    }
}
