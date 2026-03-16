<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\NotificationTemplate;

class NotificationContentResolver
{
    /**
     * Interpolate template variables into title and body.
     *
     * @param  array<string, string|int>  $variables
     * @return array{title: string, body: string, cta: string|null}
     */
    public function resolve(NotificationTemplate $template, array $variables = []): array
    {
        $title = $this->interpolate($template->title, $variables);
        $body  = $this->interpolate($template->body, $variables);
        $cta   = $template->cta ? $this->interpolate($template->cta, $variables) : null;

        return compact('title', 'body', 'cta');
    }

    /**
     * Replace {placeholder} tokens in a string.
     */
    private function interpolate(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }

        return $text;
    }

    /**
     * Build a standard payload array for notification taps.
     */
    public function buildPayload(string $type, string $subType, string $route, array $extra = []): array
    {
        return [
            'type'     => $type,
            'sub_type' => $subType,
            'route'    => $route,
            'extra'    => $extra,
        ];
    }
}
