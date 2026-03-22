<?php

namespace App\Support\Icons;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Single source of truth for shared UI icons under public/assets/icons.
 * Keys are stable URL-safe slugs from each file's basename (without extension).
 */
final class PublicIconsRegistry
{
    private const PUBLIC_SUBDIR = 'assets/icons';

    private const EXTENSIONS = ['svg', 'png', 'jpg', 'jpeg', 'webp', 'gif'];

    /**
     * Former journey_icons.php / admin keys → canonical file slug (must exist on disk).
     */
    private const LEGACY_JOURNEY_TO_CANONICAL = [
        'mosque' => '007-mosque',
        'quran' => '019-quran',
        'tasbih' => '019-tasbih',
        'crescent' => '008-moon',
        'kaaba' => '013-kaaba',
        'star' => '001-rub-el-hizb',
        'prayer' => '012-prayer',
        'lantern' => '005-lantern',
        'date_palm' => '010-palm-tree',
        'heart' => '027-give',
        'book' => '045-read',
        'bookmark' => '034-quran',
        'hands' => '011-praying',
        'sparkles' => '018-candles',
        'sun' => '038-sunset',
        'moon_star' => '008-moon',
        'compass' => '037-qibla',
        'shield' => '025-sacred',
        'lightbulb' => '004-oil-lamp',
        'leaf' => '020-desert',
        'flag' => '024-islamic',
        'gem' => '028-candle',
        'message_circle' => '040-gossip',
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
                'url' => self::assetUrlForFilename($basename),
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
     * @return array<string, string>
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
     * @return array<string, string> key => HTML (for Filament Select + allowHtml)
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

        return '<span class="fi-public-icon-option flex items-center gap-2 py-0.5">'
            .'<img src="'.$url.'" alt="" width="28" height="28" class="h-7 w-7 shrink-0 object-contain dark:opacity-95" loading="lazy" />'
            .'<span class="min-w-0 text-start">'
            .'<span class="block font-medium leading-tight">'.$label.'</span>'
            .'<span class="block text-xs text-gray-500 dark:text-gray-400 font-mono leading-tight">'.$key.'</span>'
            .'</span></span>';
    }

    public static function defaultKey(): string
    {
        $keys = self::keys();

        return $keys[0] ?? '007-mosque';
    }

    public static function isValidKey(string $key): bool
    {
        $key = trim($key);

        return $key !== '' && in_array($key, self::keys(), true);
    }

    /**
     * Canonical keys + legacy admin keys (for validation until DB is migrated).
     *
     * @return list<string>
     */
    public static function allowedKeysForValidation(): array
    {
        return array_values(array_unique(array_merge(
            self::keys(),
            array_keys(self::LEGACY_JOURNEY_TO_CANONICAL)
        )));
    }

    /**
     * Normalize stored value: null/empty → null; legacy → canonical file slug; invalid → null.
     */
    public static function canonicalizeNullable(?string $stored): ?string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return null;
        }
        if (self::isValidKey($stored)) {
            return $stored;
        }
        $lower = strtolower($stored);
        if (isset(self::LEGACY_JOURNEY_TO_CANONICAL[$lower])) {
            $mapped = self::LEGACY_JOURNEY_TO_CANONICAL[$lower];
            if (self::isValidKey($mapped)) {
                return $mapped;
            }
        }

        return null;
    }

    public static function filenameForKey(string $key): ?string
    {
        foreach (self::definitions() as $d) {
            if ($d['key'] === $key) {
                return $d['filename'];
            }
        }

        return null;
    }

    /**
     * Public /assets/icons URL (Filament preview on same host).
     */
    public static function assetUrlForFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '', $filename);

        return asset(self::PUBLIC_SUBDIR.'/'.rawurlencode($filename));
    }

    /**
     * CORS-friendly URL for mobile/web clients (Flutter Web).
     */
    public static function apiUrlForFilename(string $filename): string
    {
        $filename = str_replace(['/', '\\'], '', $filename);

        return url('api/v1/content-icons/'.rawurlencode($filename));
    }

    public static function urlForKey(string $key): ?string
    {
        $filename = self::filenameForKey($key);
        if ($filename === null) {
            return null;
        }

        return self::apiUrlForFilename($filename);
    }

    /**
     * API payload: backward-compatible `icon` + `icon_key` + `icon_url`.
     *
     * @return array{icon: ?string, icon_key: ?string, icon_url: ?string}
     */
    public static function expand(?string $raw): array
    {
        $key = self::canonicalizeNullable($raw);
        if ($key === null) {
            return [
                'icon' => null,
                'icon_key' => null,
                'icon_url' => null,
            ];
        }

        return [
            'icon' => $key,
            'icon_key' => $key,
            'icon_url' => self::urlForKey($key),
        ];
    }

    public static function keyFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);

        return Str::slug($base);
    }

    private static function labelFromFilename(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $withoutPrefix = preg_replace('/^\d+\s*[-–—]\s*/u', '', $base);

        return Str::title(str_replace(['-', '_'], ' ', $withoutPrefix !== '' ? $withoutPrefix : $base));
    }
}
