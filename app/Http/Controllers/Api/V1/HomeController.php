<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\Home\GetHomeDashboardDataAction;
use App\Domain\AppSettings\AppSetting;
use App\Domain\Home\HomeSection;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Http\Controllers\Controller;
use App\Http\Resources\HomeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __invoke(Request $request, GetHomeDashboardDataAction $action)
    {
        $data = $action->execute($request->user());

        return new HomeResource($data);
    }

    /**
     * Get home dashboard data
     * 
     * Returns aggregated content for the home screen including:
     * - Daily verse (ayah of the day)
     * - Daily hadith
     * - Home sections configuration
     * - Featured content
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $locale = $request->header('Accept-Language', 'en');
        $user = $request->user();

        // Get home sections
        $sections = HomeSection::getForLocale($locale)
            ->map(fn ($section) => $section->toApiArray($locale))
            ->values();

        // Get daily verse
        $dailyVerse = $this->getDailyVerse($locale);

        // Get daily hadith
        $dailyHadith = $this->getDailyHadith($locale);

        // Get user progress if authenticated
        $progress = null;
        if ($user) {
            $progress = $this->getUserProgress($user);
        }

        return response()->json([
            'status' => true,
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'sections' => $sections,
                'daily_verse' => $dailyVerse,
                'daily_hadith' => $dailyHadith,
                'progress' => $progress,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get daily verse (verse of the day)
     */
    private function getDailyVerse(string $locale): ?array
    {
        $dayOfYear = now()->dayOfYear;
        $totalVerses = QuranVerse::count();
        
        if ($totalVerses === 0) {
            return null;
        }

        $verseIndex = $dayOfYear % $totalVerses;
        
        $verse = QuranVerse::with(['verseTexts' => function ($q) {
            $q->forActiveLanguages()
              ->with('translation.language');
        }])
        ->orderBy('id')
        ->skip($verseIndex)
        ->first();

        if (!$verse) {
            return null;
        }

        // Get texts sorted by priority
        $texts = $verse->verseTexts->sortBy(function ($vt) {
            $code = $vt->translation->language->code ?? '';
            return match($code) {
                'en' => 1,
                'ar' => 2,
                default => 3,
            };
        });

        $primaryText = $texts->first();
        $arabicText = $texts->firstWhere(fn ($vt) => $vt->translation->language->code === 'ar');

        return [
            'id' => $verse->id,
            'surah_number' => $verse->surah_number,
            'ayah_number' => $verse->ayah_number,
            'ayah_key' => $verse->ayah_key,
            'surah_name' => $verse->surah_name,
            'text' => $primaryText?->text,
            'text_ar' => $arabicText?->text,
        ];
    }

    /**
     * Get daily hadith (hadith of the day)
     */
    private function getDailyHadith(string $locale): ?array
    {
        $config = [
            'connection' => config('content_sources.hadith.connection', 'mysql_hadith'),
            'table' => config('content_sources.hadith.table', 'hadiths'),
            'columns' => config('content_sources.hadith.columns', [
                'collection' => 'source',
                'hadith_number' => 'hadith_no',
                'text_ar' => 'text_ar',
                'text_en' => 'text_en',
            ]),
        ];

        try {
            $dayOfYear = now()->dayOfYear;
            $totalHadiths = DB::connection($config['connection'])
                ->table($config['table'])
                ->count();
            
            if ($totalHadiths === 0) {
                return null;
            }

            $hadithIndex = $dayOfYear % $totalHadiths;
            
            $hadith = DB::connection($config['connection'])
                ->table($config['table'])
                ->orderBy('id')
                ->skip($hadithIndex)
                ->first();

            if (!$hadith) {
                return null;
            }

            $cols = $config['columns'];
            $textCol = $locale === 'ar' ? $cols['text_ar'] : $cols['text_en'];

            return [
                'id' => $hadith->id,
                'collection' => $hadith->{$cols['collection']},
                'hadith_number' => $hadith->{$cols['hadith_number']},
                'text' => $hadith->{$textCol} ?? $hadith->{$cols['text_ar']},
                'text_ar' => $hadith->{$cols['text_ar']},
                'text_en' => $hadith->{$cols['text_en']},
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get user progress data
     */
    private function getUserProgress($user): array
    {
        $completedLessons = $user->lessonCompletions()->count();
        $totalLessons = \App\Domain\Lessons\Lesson::where('is_active', true)->count();
        
        // Get streak
        $streak = $user->dailyStreak;
        
        return [
            'lessons_completed' => $completedLessons,
            'lessons_total' => $totalLessons,
            'progress_percentage' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
            'current_streak' => $streak?->current_streak ?? 0,
            'longest_streak' => $streak?->longest_streak ?? 0,
        ];
    }
}
