<?php

namespace App\Domain\Notifications\Campaigns;

use App\Domain\Auth\AppUser;
use App\Domain\Lessons\Services\LessonService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class NotificationAudienceResolver
{
    public function __construct(
        private readonly LessonService $lessonService,
    ) {}

    /**
     * @param  array<string, mixed>|null  $filters
     * @return Collection<int, AppUser>
     */
    public function resolve(string $audienceType, ?array $filters): Collection
    {
        $filters = $filters ?? [];

        return match ($audienceType) {
            'all_users' => AppUser::query()->get(),
            'active_users' => $this->activeUsers(),
            'inactive_users' => $this->inactiveUsers(),
            'notifications_enabled' => $this->notificationsEnabled(),
            'journey_week' => $this->usersForJourneyWeek(
                (int) ($filters['week'] ?? $filters['journey_week'] ?? 0)
            ),
            'onboarding_incomplete' => $this->onboardingIncomplete(),
            'language' => $this->byLanguage((string) ($filters['language'] ?? '')),
            'platform' => $this->byPlatform((string) ($filters['platform'] ?? '')),
            'selected_users' => $this->selectedUsers($filters['user_ids'] ?? []),
            default => collect(),
        };
    }

    private function activeUsers(): Collection
    {
        $days = (int) config('noorly.audience.active_last_days', 14);

        return AppUser::query()
            ->where(function (Builder $q) use ($days) {
                $q->where('last_active_at', '>=', now()->subDays($days))
                    ->orWhere('updated_at', '>=', now()->subDays($days));
            })
            ->get();
    }

    private function inactiveUsers(): Collection
    {
        $days = (int) config('noorly.audience.inactive_after_days', 30);

        return AppUser::query()
            ->where(function (Builder $q) use ($days) {
                $q->whereNull('last_active_at')
                    ->orWhere('last_active_at', '<', now()->subDays($days));
            })
            ->get();
    }

    private function notificationsEnabled(): Collection
    {
        return AppUser::query()
            ->where(function (Builder $q) {
                $q->whereHas('settings', function (Builder $s) {
                    $s->where('notifications_enabled', true);
                })->orWhereDoesntHave('settings');
            })
            ->get();
    }

    /**
     * Users whose current journey week (from current day) matches.
     */
    private function usersForJourneyWeek(int $week): Collection
    {
        if ($week < 1) {
            return collect();
        }

        $ids = [];
        AppUser::query()->select('id')->chunkById(200, function ($chunk) use ($week, &$ids) {
            foreach ($chunk as $user) {
                $u = AppUser::with('onboarding')->find($user->id);
                if (! $u) {
                    continue;
                }
                $day = $this->lessonService->getUserCurrentDay($u);
                $userWeek = max(1, (int) ceil($day / 7));
                if ($userWeek === $week) {
                    $ids[] = $u->id;
                }
            }
        });

        return AppUser::query()->whereIn('id', $ids)->get();
    }

    private function onboardingIncomplete(): Collection
    {
        return AppUser::query()
            ->where(function (Builder $q) {
                $q->whereHas('onboarding', function (Builder $o) {
                    $o->whereNull('completed_at');
                })->orWhereDoesntHave('onboarding');
            })
            ->get();
    }

    private function byLanguage(string $language): Collection
    {
        $language = strtolower(substr($language, 0, 2));
        if (! in_array($language, ['ar', 'en'], true)) {
            return collect();
        }

        return AppUser::query()
            ->whereHas('settings', function (Builder $q) use ($language) {
                $q->where('language', 'like', $language.'%');
            })
            ->get();
    }

    private function byPlatform(string $platform): Collection
    {
        $platform = strtolower($platform);
        if (! in_array($platform, ['android', 'ios', 'web'], true)) {
            return collect();
        }

        $userIds = DB::table('user_notification_tokens')
            ->where('platform', $platform)
            ->where('is_active', true)
            ->distinct()
            ->pluck('user_id');

        return AppUser::query()->whereIn('id', $userIds)->get();
    }

    /**
     * @param  array<int|string>|mixed  $userIds
     */
    private function selectedUsers(mixed $userIds): Collection
    {
        if (is_string($userIds)) {
            $decoded = json_decode($userIds, true);
            $userIds = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $userIds, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (! is_array($userIds)) {
            return collect();
        }
        $ids = array_values(array_filter(array_map('intval', $userIds)));

        return AppUser::query()->whereIn('id', $ids)->get();
    }
}
