<?php

namespace App\Domain\Notifications\Campaigns;

use App\Domain\Auth\AppUser;
use App\Domain\Notifications\Push\LocalizedPushPayload;

/**
 * Picks Arabic vs English title/body from user app settings with safe fallback.
 */
final class NotificationLocalizedContentResolver
{
    public function resolveForUser(
        AppUser $user,
        ?string $titleAr,
        ?string $titleEn,
        ?string $bodyAr,
        ?string $bodyEn,
    ): LocalizedPushPayload {
        $lang = 'en';
        $settings = $user->settings;
        if ($settings && $settings->language) {
            $code = strtolower(substr($settings->language, 0, 2));
            if ($code === 'ar') {
                $lang = 'ar';
            }
        }

        if ($lang === 'ar') {
            $title = $this->firstNonEmpty($titleAr, $titleEn) ?? '';
            $body = $this->firstNonEmpty($bodyAr, $bodyEn) ?? '';
        } else {
            $title = $this->firstNonEmpty($titleEn, $titleAr) ?? '';
            $body = $this->firstNonEmpty($bodyEn, $bodyAr) ?? '';
        }

        return new LocalizedPushPayload($title, $body, $lang);
    }

    private function firstNonEmpty(?string ...$candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($c !== null && trim($c) !== '') {
                return $c;
            }
        }

        return null;
    }
}
