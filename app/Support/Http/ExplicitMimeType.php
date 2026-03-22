<?php

namespace App\Support\Http;

/**
 * Maps file extensions to Content-Type without PHP's fileinfo extension.
 *
 * Symfony's BinaryFileResponse calls File::getMimeType() when Content-Type is missing,
 * which requires ext-fileinfo. Minimal/production PHP images often omit it.
 */
final class ExplicitMimeType
{
    public static function forBasename(string $basename): string
    {
        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        return match ($ext) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}
