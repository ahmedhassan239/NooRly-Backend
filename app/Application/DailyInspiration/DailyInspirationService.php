<?php

namespace App\Application\DailyInspiration;

use App\Domain\Adhkar\Adhkar;
use App\Domain\AppSettings\AppSetting;
use App\Domain\Auth\AppUser;
use App\Domain\DailyInspiration\UserDailyInspiration;
use App\Domain\Duas\Dua;
use App\Domain\QuranAllLang\Models\QuranVerse;
use Carbon\Carbon;
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

        $payload = $this->buildItemPayload($record->type, (int) $record->item_id, $locale);

        return [
            'type' => $record->type,
            'refresh_after_seconds' => $refreshSeconds,
            'expires_at' => $record->expires_at->toIso8601String(),
            'item' => $payload,
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
    }

    private function pickRandomVerseId(?int $excludeId): ?int
    {
        $query = QuranVerse::query()->select('id');
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        $id = $query->inRandomOrder()->value('id');

        return $id !== null ? (int) $id : null;
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
