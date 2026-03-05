<?php

namespace App\Application\DailyInspiration;

use App\Application\DailyInspiration\Strategies\CollectionStrategyInterface;
use App\Application\DailyInspiration\Strategies\HadithCollectionStrategy;
use App\Application\DailyInspiration\Strategies\VerseCollectionStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Daily inspiration from library collections only.
 * Picks random hadith or ayah from HadithCollection / VerseCollection pivots, fetches details from external DB.
 */
class DailyInspirationFromCollectionsService
{
    private const CACHE_PREFIX = 'daily_inspiration:';

    /** @var array<string, CollectionStrategyInterface> */
    private array $strategies;

    public function __construct(
        HadithCollectionStrategy $hadithStrategy,
        VerseCollectionStrategy $verseStrategy
    ) {
        $this->strategies = [
            'hadith' => $hadithStrategy,
            'ayah' => $verseStrategy,
        ];
    }

    /**
     * Get daily inspiration (from cache or fresh pick). Response shape for API + optional debug.
     *
     * @return array{data: array, debug?: array}
     */
    public function get(Request $request): array
    {
        $locale = $this->localeFromRequest($request);
        $userId = $request->user()?->id;
        $date = now()->format('Y-m-d');
        $cacheKey = $this->cacheKey($date, $locale, $userId);
        $forceType = $this->validateAndGetForceType($request);
        $refresh = $request->boolean('refresh');
        $debug = config('app.debug') || $request->boolean('debug');

        if (! $refresh) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null && is_array($cached)) {
                $result = ['data' => $cached];
                if ($debug) {
                    $result['debug'] = [
                        'picked_type' => $cached['type'] ?? null,
                        'picked_collection_id' => $cached['collection_id'] ?? null,
                        'picked_item_id' => $cached['id'] ?? null,
                        'counts' => $this->counts(),
                        'strategy' => 'collection_pivot_random_id + external_db_fetch',
                        'cache_key' => $cacheKey,
                        'forced_type' => $forceType,
                        'from_cache' => true,
                    ];
                }

                return $result;
            }
        }

        $payload = $this->pickOne($locale, $forceType);
        Cache::put($cacheKey, $payload, now()->endOfDay()->addSecond());

        $result = ['data' => $payload];
        if ($debug) {
            $result['debug'] = [
                'picked_type' => $payload['type'],
                'picked_collection_id' => $payload['collection_id'],
                'picked_item_id' => $payload['id'],
                'counts' => $this->counts(),
                'strategy' => 'collection_pivot_random_id + external_db_fetch',
                'cache_key' => $cacheKey,
                'forced_type' => $forceType,
            ];
        }

        return $result;
    }

    /**
     * Validate ?type= and return normalized value or null.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateForceType(Request $request): void
    {
        $type = $request->query('type');
        if ($type === null || $type === '') {
            return;
        }
        $type = strtolower(trim($type));
        if (! in_array($type, ['hadith', 'ayah'], true)) {
            validator(['type' => $type], ['type' => 'in:hadith,ayah'])->validate();
        }
    }

    private function validateAndGetForceType(Request $request): ?string
    {
        $type = $request->query('type');
        if ($type === null || $type === '') {
            return null;
        }
        $type = strtolower(trim($type));
        if (! in_array($type, ['hadith', 'ayah'], true)) {
            return null;
        }

        return $type;
    }

    private function localeFromRequest(Request $request): string
    {
        $header = $request->header('Accept-Language', 'en');
        $first = trim(explode(',', $header)[0] ?? 'en');
        $lang = trim(explode(';', $first)[0] ?? 'en');

        return strlen($lang) >= 2 ? strtolower(substr($lang, 0, 2)) : 'en';
    }

    private function cacheKey(string $date, string $locale, mixed $userId): string
    {
        $userPart = $userId !== null ? (string) $userId : 'guest';

        return self::CACHE_PREFIX.$date.':'.$locale.':'.$userPart;
    }

    /** @return array{hadith_collections: int, verse_collections: int} */
    private function counts(): array
    {
        return [
            'hadith_collections' => $this->strategies['hadith']->getAvailableCollectionCount(),
            'verse_collections' => $this->strategies['ayah']->getAvailableCollectionCount(),
        ];
    }

    /**
     * Pick one item from library collections.
     *
     * @return array{type: string, id: int, collection_id: int, title: string, arabic: string, translation: string|null, source: string|null}
     */
    private function pickOne(string $locale, ?string $forceType): array
    {
        $available = [];
        if ($forceType !== null) {
            if (! isset($this->strategies[$forceType])) {
                throw new \RuntimeException('Invalid forced type.');
            }
            $strategy = $this->strategies[$forceType];
            if (! $strategy->isAvailable()) {
                throw new \RuntimeException("No content available for type: {$forceType}.");
            }

            return $strategy->pick($locale);
        }

        foreach ($this->strategies as $strategy) {
            if ($strategy->isAvailable()) {
                $available[] = $strategy;
            }
        }

        if ($available === []) {
            throw new \RuntimeException('No library content available for daily inspiration.');
        }

        $strategy = $available[array_rand($available)];

        return $strategy->pick($locale);
    }
}
