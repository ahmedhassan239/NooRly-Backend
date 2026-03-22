<?php

namespace App\Support\Ramadan;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Source of truth for Ramadan guide icons under public/assets/ramadan.
 * Keys are URL-safe slugs derived from filenames (without extension).
 */
final class RamadanIconRegistry
{
    private const PUBLIC_SUBDIR = 'assets/ramadan';

    private const EXTENSIONS = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'];

    /**
     * Legacy DB keys (from early seeders / admin) → canonical slug keys.
     * Used for icon_url resolution and normalizing values on save.
     */
    private const LEGACY_TO_CANONICAL = [
        'moon' => 'ramadhan-night-icon',
        'sun' => 'ramadhan-day-icon',
        'warning' => 'halal-icon',
        'hands' => 'pray-icon',
        'sparkle' => 'eid-icon',
        'sparkles' => 'eid-icon',
        'mosque' => 'mosque-icon',
        'food' => 'meal-bowl-icon',
        'strength' => 'muslim-man-icon',
        'star' => 'ramadhan-night-icon',
        'money' => 'zakat-icon',
        'celebration' => 'eid-icon',
        'refresh' => 'app-islamic-icon',
    ];

    /**
     * @return list<array{key: string, label: string, filename: string, extension: string, url: string}>
     */
    public static function definitions(): array
    {
        $dir = public_path(self::PUBLIC_SUBDIR);
        if (! is_dir($dir)) {
            return [];
        }

        $out = [];
        foreach (File::files($dir) as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, self::EXTENSIONS, true)) {
                continue;
            }
            $basename = $file->getFilename();
            $key = self::keyFromFilename($basename);
            $out[] = [
                'key' => $key,
                'label' => self::labelFromFilename($basename),
                'filename' => $basename,
                'extension' => $ext,
                'url' => self::urlForFilename($basename),
            ];
        }

        usort($out, fn (array $a, array $b) => strcmp($a['label'], $b['label']));

        return $out;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_values(array_unique(array_map(
            fn (array $d) => $d['key'],
            self::definitions()
        )));
    }

    /**
     * @return array<string, string> key => label (for Filament Select)
     */
    public static function optionsForSelect(): array
    {
        $opts = [];
        foreach (self::definitions() as $d) {
            $opts[$d['key']] = $d['label'].' ('.$d['key'].')';
        }

        return $opts;
    }

    /**
     * HTML label with a small preview image (for Filament Select + allowHtml()).
     *
     * @return array<string, string> key => HTML string
     */
    public static function optionsForSelectWithImages(): array
    {
        $opts = [];
        foreach (self::definitions() as $d) {
            $opts[$d['key']] = self::optionLabelHtmlForDefinition($d);
        }

        return $opts;
    }

    /**
     * @param  array{key: string, label: string, filename: string, extension: string, url: string}  $d
     */
    public static function optionLabelHtmlForDefinition(array $d): string
    {
        $url = e($d['url']);
        $label = e($d['label']);
        $key = e($d['key']);

        return '<span class="fi-ramadan-icon-option flex items-center gap-2 py-0.5">'
            .'<img src="'.$url.'" alt="" width="32" height="32" class="h-8 w-8 shrink-0 object-contain dark:opacity-95" loading="lazy" />'
            .'<span class="min-w-0 text-start">'
            .'<span class="block font-medium leading-tight">'.$label.'</span>'
            .'<span class="block text-xs text-gray-500 dark:text-gray-400 font-mono leading-tight">'.$key.'</span>'
            .'</span></span>';
    }

    public static function optionLabelHtmlForKey(string $key): ?string
    {
        foreach (self::definitions() as $d) {
            if ($d['key'] === $key) {
                return self::optionLabelHtmlForDefinition($d);
            }
        }

        return null;
    }

    public static function defaultKey(): string
    {
        $keys = self::keys();

        return $keys[0] ?? 'ramadhan-night-icon';
    }

    /**
     * True if key exists in scanned assets (canonical).
     */
    public static function isValidCanonicalKey(string $key): bool
    {
        $key = trim($key);

        return $key !== '' && in_array($key, self::keys(), true);
    }

    /**
     * Keys allowed when validating form input: canonical + legacy aliases.
     *
     * @return list<string>
     */
    public static function allowedKeysForValidation(): array
    {
        return array_values(array_unique(array_merge(
            self::keys(),
            array_keys(self::LEGACY_TO_CANONICAL)
        )));
    }

    /**
     * Normalize stored value to a canonical key that exists in the registry.
     */
    public static function canonicalizeStoredKey(?string $stored): string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return self::defaultKey();
        }
        if (self::isValidCanonicalKey($stored)) {
            return $stored;
        }
        $lower = strtolower($stored);
        if (isset(self::LEGACY_TO_CANONICAL[$lower])) {
            $mapped = self::LEGACY_TO_CANONICAL[$lower];
            if (self::isValidCanonicalKey($mapped)) {
                return $mapped;
            }
        }

        return self::defaultKey();
    }

    /**
     * Public URL for a file in assets/ramadan (handles spaces in filenames).
     */
    public static function urlForFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '', $filename);

        return asset(self::PUBLIC_SUBDIR.'/'.rawurlencode($filename));
    }

    /**
     * URL for mobile/web clients: served via API with CORS (Flutter Web cannot load
     * /public/assets from another origin without Access-Control-Allow-Origin).
     */
    public static function apiAssetUrlForFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '', $filename);

        return url('api/v1/ramadan-guide/icons/'.rawurlencode($filename));
    }

    /**
     * Resolve absolute icon URL for API from DB value (canonical or legacy).
     */
    public static function urlForStoredIcon(?string $stored): string
    {
        $canonical = self::canonicalizeStoredKey($stored);
        foreach (self::definitions() as $d) {
            if ($d['key'] === $canonical) {
                return self::apiAssetUrlForFilename($d['filename']);
            }
        }

        // Fallback: first icon
        $defs = self::definitions();
        if ($defs !== []) {
            return self::apiAssetUrlForFilename($defs[0]['filename']);
        }

        return url('api/v1/ramadan-guide/icons/');
    }

    public static function keyFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return Str::slug($base);
    }

    private static function labelFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return Str::title(str_replace(['_', '-'], ' ', $base));
    }
}
