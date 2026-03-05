<?php

namespace App\Application\DailyInspiration;

use App\Domain\Adhkar\Adhkar;
use App\Domain\AppSettings\AppSetting;
use App\Domain\Auth\AppUser;
use App\Domain\DailyInspiration\UserDailyInspiration;
use App\Domain\Duas\Dua;
use App\Domain\QuranAllLang\Models\QuranVerse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyInspirationService
{
    private const TYPES = ['hadith', 'verse', 'dua', 'adhkar'];

    private const DEFAULT_REFRESH_HOURS = 3;

    public function getRefreshIntervalHours(): int
    {
        $hours = (int) AppSetting::get('daily_content_refresh_hour', self::DEFAULT_REFRESH_HOURS);

        return $hours > 0 ? $hours : self::DEFAULT_REFRESH_HOURS;
    }

    /**
     * Get or create the daily inspiration for the user.
     * Uses lockForUpdate to avoid double writes under concurrency.
     */
    public function getOrCreate(AppUser $user, string $locale): array
    {
        $intervalHours = $this->getRefreshIntervalHours();
        $refreshSeconds = $intervalHours * 3600;

        $record = DB::transaction(function () use ($user, $intervalHours) {
            $record = UserDailyInspiration::where('app_user_id', $user->id)->lockForUpdate()->first();

            if ($record && ! $record->isExpired()) {
                return $record;
            }

            $previousItemId = $record?->item_id;
            $previousType = $record?->type;

            $type = $this->pickRandomType();
            $itemId = $this->pickRandomItem($type, $previousType === $type ? $previousItemId : null);

            if ($itemId === null) {
                foreach (self::TYPES as $fallbackType) {
                    if ($fallbackType === $type) {
                        continue;
                    }
                    $itemId = $this->pickRandomItem($fallbackType, null);
                    if ($itemId !== null) {
                        $type = $fallbackType;
                        break;
                    }
                }
            }
            if ($itemId === null) {
                throw new \RuntimeException('No content available for daily inspiration.');
            }

            $selectedAt = Carbon::now();
            $expiresAt = $selectedAt->copy()->addHours($intervalHours);

            $data = [
                'app_user_id' => $user->id,
                'type' => $type,
                'item_id' => $itemId,
                'selected_at' => $selectedAt,
                'expires_at' => $expiresAt,
                'previous_item_id' => $previousItemId,
            ];

            $record = UserDailyInspiration::updateOrCreate(
                ['app_user_id' => $user->id],
                $data
            );

            Log::info('Daily inspiration generated', [
                'user_id' => $user->id,
                'type' => $type,
                'item_id' => $itemId,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return $record;
        });

        $unified = $this->buildUnifiedPayload($record->type, (int) $record->item_id, $locale);

        return array_merge($unified, [
            'refresh_after_seconds' => $refreshSeconds,
            'expires_at' => $record->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Get a single daily inspiration for the whole day (same for all users).
     * Cached until end of day. Returns a unified shape for the Flutter app.
     *
     * @return array{type: string, title: string, arabic: string, translation: string, source?: string, surah?: string}
     */
    public function getGlobalDailyUnified(string $locale): array
    {
        $cacheKey = 'daily_inspiration_unified:'.now()->format('Y-m-d');
        $ttl = now()->endOfDay()->addSecond();

        return Cache::remember($cacheKey, $ttl, function () use ($locale) {
            $locale = strlen($locale) >= 2 ? strtolower(substr($locale, 0, 2)) : 'en';

            $type = $this->pickRandomType();
            $itemId = $this->pickRandomItem($type, null);

            if ($itemId === null) {
                foreach (self::TYPES as $fallbackType) {
                    if ($fallbackType === $type) {
                        continue;
                    }
                    $itemId = $this->pickRandomItem($fallbackType, null);
                    if ($itemId !== null) {
                        $type = $fallbackType;
                        break;
                    }
                }
            }

            if ($itemId === null) {
                throw new \RuntimeException('No content available for daily inspiration.');
            }

            $tableName = $this->getTableNameForType($type);
            Log::debug('Daily inspiration selected', [
                'type' => $this->publicType($type),
                'record_id' => $itemId,
                'table' => $tableName,
            ]);

            return $this->buildUnifiedPayload($type, $itemId, $locale);
        });
    }

    private function getTableNameForType(string $internalType): string
    {
        return match ($internalType) {
            'hadith' => config('content_sources.hadith.table', 'hadiths'),
            'verse' => (new QuranVerse)->getTable(),
            'dua' => (new Dua)->getTable(),
            'adhkar' => (new Adhkar)->getTable(),
            default => 'unknown',
        };
    }

    /** @return 'ayah'|'hadith'|'dhikr'|'dua' */
    private function publicType(string $internalType): string
    {
        return match ($internalType) {
            'verse' => 'ayah',
            'adhkar' => 'dhikr',
            default => $internalType,
        };
    }

    /**
     * Build unified response: type, title, arabic, translation, and source or surah.
     *
     * @return array{type: string, title: string, arabic: string, translation: string, source?: string, surah?: string}
     */
    private function buildUnifiedPayload(string $internalType, int $itemId, string $locale): array
    {
        $type = $this->publicType($internalType);

        return match ($internalType) {
            'hadith' => $this->buildUnifiedHadith($itemId),
            'verse' => $this->buildUnifiedAyah($itemId, $locale),
            'dua' => $this->buildUnifiedDua($itemId, $locale),
            'adhkar' => $this->buildUnifiedDhikr($itemId),
            default => [],
        };
    }

    /** @return array{type: 'hadith', title: string, arabic: string, translation: string, source: string} */
    private function buildUnifiedHadith(int $itemId): array
    {
        $config = $this->getHadithConfig();
        $cols = $config['columns'];

        $hadith = DB::connection($config['connection'])
            ->table($config['table'])
            ->where('id', $itemId)
            ->first();

        if (! $hadith) {
            return ['type' => 'hadith', 'id' => $itemId, 'title' => 'Hadith', 'arabic' => '', 'translation' => '', 'source' => ''];
        }

        return [
            'type' => 'hadith',
            'id' => (int) $hadith->id,
            'title' => 'Hadith',
            'arabic' => (string) $hadith->{$cols['text_ar']},
            'translation' => (string) $hadith->{$cols['text_en']},
            'source' => $this->formatHadithCollectionName($hadith->{$cols['collection']}),
        ];
    }

    /** @return array{type: 'ayah', title: string, arabic: string, translation: string, surah: string} */
    private function buildUnifiedAyah(int $itemId, string $locale): array
    {
        $verse = QuranVerse::with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()->with('translation.language');
        }])->find($itemId);

        if (! $verse) {
            return ['type' => 'ayah', 'id' => $itemId, 'title' => 'Quran', 'arabic' => '', 'translation' => '', 'surah' => '', 'ayah_number' => null];
        }

        $texts = $verse->verseTexts->sortBy(fn ($vt) => match ($vt->translation->language->code ?? '') {
            'en' => 1, 'ar' => 2, default => 3,
        });
        $primaryText = $texts->first();
        $arabicText = $texts->firstWhere(fn ($vt) => ($vt->translation->language->code ?? '') === 'ar');

        $surahLabel = $verse->surah_name.' '.$verse->ayah_number;

        return [
            'type' => 'ayah',
            'id' => (int) $verse->id,
            'title' => 'Quran',
            'arabic' => (string) ($arabicText?->text ?? ''),
            'translation' => (string) ($primaryText?->text ?? ''),
            'surah' => $surahLabel,
            'ayah_number' => (int) $verse->ayah_number,
        ];
    }

    /** @return array{type: 'dua', title: string, arabic: string, translation: string, source: string} */
    private function buildUnifiedDua(int $itemId, string $locale): array
    {
        $dua = Dua::where('is_active', true)->find($itemId);

        if (! $dua) {
            return ['type' => 'dua', 'id' => $itemId, 'title' => 'Dua', 'arabic' => '', 'translation' => '', 'source' => ''];
        }

        $textAr = $dua->getTranslation('text', 'ar');
        $textLocale = $dua->getTranslation('text', $locale);

        return [
            'type' => 'dua',
            'id' => (int) $dua->id,
            'title' => $dua->getTranslation('name', $locale) ?: 'Dua',
            'arabic' => (string) $textAr,
            'translation' => (string) $textLocale,
            'source' => (string) ($dua->source ?? ''),
        ];
    }

    /** @return array{type: 'dhikr', title: string, arabic: string, translation: string, source: string} */
    private function buildUnifiedDhikr(int $itemId): array
    {
        $adhkar = Adhkar::active()->find($itemId);

        if (! $adhkar) {
            return ['type' => 'dhikr', 'id' => $itemId, 'title' => 'Dhikr', 'arabic' => '', 'translation' => '', 'source' => ''];
        }

        $text = $adhkar->text ?? [];

        return [
            'type' => 'dhikr',
            'id' => (int) $adhkar->id,
            'title' => 'Dhikr',
            'arabic' => (string) ($text['ar'] ?? ''),
            'translation' => (string) ($text['en'] ?? ''),
            'source' => (string) ($adhkar->source ?? ''),
        ];
    }

    private function pickRandomType(): string
    {
        return self::TYPES[array_rand(self::TYPES)];
    }

    private function pickRandomItem(string $type, ?int $excludeId): ?int
    {
        return match ($type) {
            'hadith' => $this->pickRandomHadithId($excludeId),
            'verse' => $this->pickRandomVerseId($excludeId),
            'dua' => $this->pickRandomDuaId($excludeId),
            'adhkar' => $this->pickRandomAdhkarId($excludeId),
            default => null,
        };
    }

    private function pickRandomHadithId(?int $excludeId): ?int
    {
        try {
            $config = $this->getHadithConfig();
            $query = DB::connection($config['connection'])
                ->table($config['table'])
                ->select('id');

            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }

            $ids = $query->pluck('id')->toArray();
            if (empty($ids)) {
                return null;
            }

            return (int) $ids[array_rand($ids)];
        } catch (\Throwable) {
            return null;
        }
    }

    private function pickRandomVerseId(?int $excludeId): ?int
    {
        try {
            $query = QuranVerse::query()->select('id');
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            $id = $query->inRandomOrder()->value('id');

            return $id !== null ? (int) $id : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function pickRandomDuaId(?int $excludeId): ?int
    {
        $query = Dua::where('is_active', true)->select('id');
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        $id = $query->inRandomOrder()->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function pickRandomAdhkarId(?int $excludeId): ?int
    {
        $query = Adhkar::active()->select('id');
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        $id = $query->inRandomOrder()->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function buildItemPayload(string $type, int $itemId, string $locale): array
    {
        $locale = strlen($locale) >= 2 ? strtolower(substr($locale, 0, 2)) : 'en';

        return match ($type) {
            'hadith' => $this->buildHadithPayload($itemId, $locale),
            'verse' => $this->buildVersePayload($itemId, $locale),
            'dua' => $this->buildDuaPayload($itemId, $locale),
            'adhkar' => $this->buildAdhkarPayload($itemId, $locale),
            default => [],
        };
    }

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

    private function buildHadithPayload(int $itemId, string $locale): array
    {
        $config = $this->getHadithConfig();
        $cols = $config['columns'];

        $hadith = DB::connection($config['connection'])
            ->table($config['table'])
            ->where('id', $itemId)
            ->first();

        if (! $hadith) {
            return [];
        }

        return [
            'id' => $hadith->id,
            'collection' => $hadith->{$cols['collection']},
            'collection_name' => $this->formatHadithCollectionName($hadith->{$cols['collection']}),
            'hadith_number' => $hadith->{$cols['hadith_number']},
            'chapter_number' => $hadith->{$cols['book_number']} ?? null,
            'text_ar' => $hadith->{$cols['text_ar']},
            'text_en' => $hadith->{$cols['text_en']},
            'text' => $locale === 'ar' ? $hadith->{$cols['text_ar']} : $hadith->{$cols['text_en']},
        ];
    }

    private function formatHadithCollectionName(string $source): string
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

    private function buildVersePayload(int $itemId, string $locale): array
    {
        $verse = QuranVerse::with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()->with('translation.language');
        }])->find($itemId);

        if (! $verse) {
            return [];
        }

        $texts = $verse->verseTexts->sortBy(function ($vt) {
            $code = $vt->translation->language->code ?? '';

            return match ($code) {
                'en' => 1,
                'ar' => 2,
                default => 3,
            };
        });

        $primaryText = $texts->first();
        $arabicText = $texts->firstWhere(fn ($vt) => ($vt->translation->language->code ?? '') === 'ar');

        $data = [
            'id' => $verse->id,
            'surah_number' => $verse->surah_number,
            'ayah_number' => $verse->ayah_number,
            'ayah_key' => $verse->ayah_key,
            'surah_name' => $verse->surah_name,
            'text' => $primaryText?->text,
            'text_ar' => $arabicText?->text,
        ];

        $translations = $verse->verseTexts
            ->sortBy(fn ($vt) => match (($vt->translation->language->code ?? '')) {
                'en' => 1, 'ar' => 2, default => 3
            })
            ->groupBy(fn ($vt) => $vt->translation->language->name)
            ->map(fn ($group) => $group->map(fn ($vt) => [
                'translator' => $vt->translation->source_name,
                'text' => $vt->text,
                'direction' => $vt->translation->language->direction,
            ]));

        $data['translations'] = $translations;

        return $data;
    }

    private function buildDuaPayload(int $itemId, string $locale): array
    {
        $dua = Dua::with(['categories.translations', 'quranAyahs', 'hadithItems'])
            ->where('is_active', true)
            ->find($itemId);

        if (! $dua) {
            return [];
        }

        $textAr = $dua->getTranslation('text', 'ar');
        $textLocale = $dua->getTranslation('text', $locale);

        $data = [
            'id' => $dua->id,
            'name' => $dua->getTranslation('name', $locale),
            'title' => $dua->getTranslation('name', $locale),
            'text' => $textLocale,
            'text_ar' => $textAr,
            'arabic_text' => $textAr,
            'translation' => $textLocale,
            'transliteration' => $dua->transliteration,
            'source' => $dua->source,
            'is_featured' => $dua->is_featured,
            'categories' => $dua->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->getTranslation($locale)?->name ?? $cat->translations->first()?->name ?? '',
            ]),
            'quran_references' => $dua->quranAyahs->map(fn ($verse) => [
                'id' => $verse->id,
                'surah_number' => $verse->surah_number,
                'ayah_number' => $verse->ayah_number,
                'ayah_key' => $verse->ayah_key,
            ]),
            'hadith_references' => $dua->hadithItems->map(fn ($h) => [
                'id' => $h->id,
                'source' => $h->source ?? $h->collection ?? null,
                'number' => $h->hadith_no ?? $h->number ?? null,
            ]),
        ];

        return $data;
    }

    private function buildAdhkarPayload(int $itemId, string $locale): array
    {
        $adhkar = Adhkar::with('category')->active()->find($itemId);

        if (! $adhkar) {
            return [];
        }

        $text = $adhkar->text ?? [];
        $reward = $adhkar->reward ?? [];

        return [
            'id' => $adhkar->id,
            'category_id' => $adhkar->category_id,
            'repeat_count' => (int) $adhkar->count,
            'source' => $adhkar->source ?? '',
            'audio_url' => $adhkar->audio_url ?? null,
            'arabic_text' => (string) ($text['ar'] ?? ''),
            'transliteration' => '',
            'english_text' => (string) ($text['en'] ?? ''),
            'reward' => [
                'ar' => (string) ($reward['ar'] ?? ''),
                'en' => (string) ($reward['en'] ?? ''),
            ],
        ];
    }
}
