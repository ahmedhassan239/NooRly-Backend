<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Adhkar\Adhkar;
use App\Domain\Duas\Dua;
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
            'type' => ['nullable', Rule::in(array_merge(self::SUPPORTED_TYPES, ['all']))],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $type = $request->query('type');
        if ($type === 'all') {
            return $this->savedAllUnified($request);
        }
        if ($type === 'hadith') {
            return $this->savedHadithIndex($request);
        }
        if ($type === 'verse') {
            return $this->savedVersesIndex($request);
        }
        if ($type === 'adhkar') {
            return $this->savedAdhkarIndex($request);
        }
        if ($type === 'dua') {
            return $this->savedDuasIndex($request);
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
        if (! in_array($type, self::SUPPORTED_TYPES)) {
            return $this->errorResponse(
                'Invalid item type. Supported types: '.implode(', ', self::SUPPORTED_TYPES),
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
        if (! in_array($type, self::SUPPORTED_TYPES)) {
            return $this->errorResponse(
                'Invalid item type. Supported types: '.implode(', ', self::SUPPORTED_TYPES),
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
     * GET /saved?type=all — unified list of all saved items (paginated, normalized shape).
     */
    private function savedAllUnified(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage < 1 ? 20 : min($perPage, 100);
        $page = max(1, (int) $request->query('page', 1));

        $savedQuery = $user->savedItems()
            ->whereIn('item_type', ['dua', 'hadith', 'verse', 'adhkar'])
            ->orderByDesc('created_at');

        $total = $savedQuery->count();
        $offset = ($page - 1) * $perPage;
        $rows = $savedQuery->offset($offset)->limit($perPage)->get();

        $byType = $rows->groupBy('item_type');
        $hadithMap = $this->hydrateHadithMap($request, $byType->get('hadith', collect())->pluck('item_id')->map(fn ($id) => is_numeric($id) ? (int) $id : $id)->all());
        $verseMap = $this->hydrateVerseMap($request, $byType->get('verse', collect())->pluck('item_id')->map(fn ($id) => is_numeric($id) ? (int) $id : $id)->all());
        $duaMap = $this->hydrateDuaMap($request, $byType->get('dua', collect())->pluck('item_id')->map(fn ($id) => is_numeric($id) ? (int) $id : $id)->all());
        $adhkarMap = $this->hydrateAdhkarMap($request, $byType->get('adhkar', collect())->pluck('item_id')->map(fn ($id) => is_numeric($id) ? (int) $id : $id)->all());

        $items = [];
        foreach ($rows as $row) {
            $itemId = $row->item_id;
            $refId = is_numeric($itemId) ? (int) $itemId : $itemId;
            $entry = match ($row->item_type) {
                'hadith' => $hadithMap[$refId] ?? null,
                'verse' => $verseMap[$refId] ?? null,
                'dua' => $duaMap[$refId] ?? null,
                'adhkar' => $adhkarMap[$refId] ?? null,
                default => null,
            };
            if ($entry !== null) {
                $items[] = array_merge($entry, ['id' => (int) $row->id, 'reference_id' => $refId]);
            }
        }

        return $this->successResponse([
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => $offset + $rows->count() < $total,
            ],
        ], null, 200);
    }

    /** @return array<int|string, array{type: string, title: string, arabic: string|null, translation: string|null, source: string|null}> */
    private function hydrateHadithMap(Request $request, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $config = $this->getHadithConfig();
        $locale = $this->getPreferredLocale($request);
        $textCol = $locale === 'ar' ? ($config['columns']['text_ar'] ?? 'text_ar') : ($config['columns']['text_en'] ?? 'text_en');
        $rows = DB::connection($config['connection'])->table($config['table'])->whereIn('id', $itemIds)->get()->keyBy('id');
        $out = [];
        foreach ($itemIds as $id) {
            $row = $rows->get($id);
            if (! $row) {
                continue;
            }
            $source = $row->{$config['columns']['collection'] ?? 'source'} ?? '';
            $out[$id] = [
                'type' => 'hadith',
                'title' => $this->formatCollectionName((string) $source),
                'arabic' => $row->{$config['columns']['text_ar'] ?? 'text_ar'} ?? null,
                'translation' => $row->{$config['columns']['text_en'] ?? 'text_en'} ?? null,
                'source' => $this->formatCollectionName((string) $source),
            ];
        }

        return $out;
    }

    /** @return array<int|string, array{type: string, title: string, arabic: string|null, translation: string|null, source: string|null}> */
    private function hydrateVerseMap(Request $request, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $locale = $this->getPreferredLocale($request);
        $verses = QuranVerse::whereIn('id', $itemIds)->with(['verseTexts' => fn ($q) => $q->forActiveLanguages()->with('translation.language')])->get()->keyBy('id');
        $out = [];
        foreach ($itemIds as $id) {
            $verse = $verses->get($id);
            if (! $verse) {
                continue;
            }
            $ref = "{$verse->surah_number}:{$verse->ayah_number}";
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
            $out[$id] = [
                'type' => 'verse',
                'title' => SurahHelper::getName($verse->surah_number).' '.$ref,
                'arabic' => $textAr,
                'translation' => $locale === 'ar' ? $textAr : $textEn,
                'source' => SurahHelper::getName($verse->surah_number),
            ];
        }

        return $out;
    }

    /** @return array<int|string, array{type: string, title: string, arabic: string|null, translation: string|null, source: string|null}> */
    private function hydrateDuaMap(Request $request, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $locale = $this->getPreferredLocale($request);
        $duas = Dua::where('is_active', true)->whereIn('id', $itemIds)->with('categories')->get()->keyBy('id');
        $out = [];
        foreach ($itemIds as $id) {
            $dua = $duas->get($id);
            if (! $dua) {
                continue;
            }
            $title = $this->formatDuaKey($dua->dua_key ?? '');
            $out[$id] = [
                'type' => 'dua',
                'title' => $title,
                'arabic' => $dua->getTranslation('text', 'ar'),
                'translation' => $dua->getTranslation('text', $locale),
                'source' => $dua->source ?? null,
            ];
        }

        return $out;
    }

    /** @return array<int|string, array{type: string, title: string, arabic: string|null, translation: string|null, source: string|null}> */
    private function hydrateAdhkarMap(Request $request, array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $locale = $this->getPreferredLocale($request);
        $adhkarModels = Adhkar::active()->with('category')->whereIn('id', $itemIds)->get()->keyBy('id');
        $out = [];
        foreach ($itemIds as $id) {
            $adhkar = $adhkarModels->get($id);
            if (! $adhkar) {
                continue;
            }
            $arr = $adhkar->toApiArray($locale);
            $content = $arr['content'] ?? [];
            $text = $content['text'] ?? [];
            $title = $arr['category']['name'] ?? 'Dhikr';
            $out[$id] = [
                'type' => 'adhkar',
                'title' => is_string($title) ? $title : 'Dhikr',
                'arabic' => $text['ar'] ?? null,
                'translation' => $text['en'] ?? $text['ar'] ?? null,
                'source' => $arr['source'] ?? null,
            ];
        }

        return $out;
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
            if (! $row) {
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
     * Convert dua_key slug to human-readable title: "before-eating" -> "Before Eating".
     */
    private function formatDuaKey(string $key): string
    {
        if ($key === '') {
            return 'Dua';
        }

        return ucwords(str_replace(['-', '_'], ' ', $key));
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
            if (! $verse) {
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

    /**
     * GET /saved?type=adhkar — hydrated saved adhkar (latest first, paginated).
     */
    private function savedAdhkarIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage < 1 ? 20 : min($perPage, 100);

        $savedQuery = $user->savedItems()
            ->where('item_type', 'adhkar')
            ->orderByDesc('created_at');

        $total = $savedQuery->count();
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        $savedRows = $savedQuery->offset($offset)->limit($perPage)->get(['id', 'item_id', 'created_at']);

        $adhkarIds = $savedRows->pluck('item_id')->map(function ($id) {
            return is_numeric($id) ? (int) $id : $id;
        })->all();

        if (count($adhkarIds) === 0) {
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

        $adhkarModels = Adhkar::active()
            ->with('category')
            ->whereIn('id', $adhkarIds)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($adhkarIds as $aid) {
            $adhkar = $adhkarModels->get($aid);
            if (! $adhkar) {
                continue;
            }
            $items[] = array_merge($adhkar->toApiArray($locale), ['is_saved' => true]);
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
     * GET /saved?type=dua — hydrated saved duas (latest first, paginated).
     */
    private function savedDuasIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage < 1 ? 20 : min($perPage, 100);

        $savedQuery = $user->savedItems()
            ->where('item_type', 'dua')
            ->orderByDesc('created_at');

        $total = $savedQuery->count();
        $page = max(1, (int) $request->query('page', 1));
        $offset = ($page - 1) * $perPage;

        $savedRows = $savedQuery->offset($offset)->limit($perPage)->get(['id', 'item_id', 'created_at']);

        $duaIds = $savedRows->pluck('item_id')->map(function ($id) {
            return is_numeric($id) ? (int) $id : $id;
        })->all();

        if (count($duaIds) === 0) {
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

        $duaModels = Dua::where('is_active', true)
            ->with('categories')
            ->whereIn('id', $duaIds)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($duaIds as $did) {
            $dua = $duaModels->get($did);
            if (! $dua) {
                continue;
            }
            $textAr = $dua->getTranslation('text', 'ar');
            $textLocale = $dua->getTranslation('text', $locale);
            $title = $this->formatDuaKey($dua->dua_key ?? '');
            $items[] = [
                'id' => $dua->id,
                'item_id' => $dua->id,
                'title' => $title,
                'title_ar' => $title,
                'text' => $textLocale,
                'text_ar' => $textAr,
                'text_en' => $dua->getTranslation('text', 'en'),
                'transliteration' => $dua->transliteration,
                'source' => $dua->source,
                'category_id' => $dua->categories->first()?->id,
                'category_name' => $dua->categories->first()?->getName($locale),
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
