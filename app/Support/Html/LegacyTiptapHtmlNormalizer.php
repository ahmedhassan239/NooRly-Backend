<?php

namespace App\Support\Html;

class LegacyTiptapHtmlNormalizer
{
    /**
     * Strip legacy inline "black" text colors that break dark mode.
     *
     * We only remove explicit black-ish colors (black / #000 / rgb(0,0,0) / rgba(0,0,0,1)).
     * Any other intentional colors are preserved.
     */
    public static function stripInlineBlackTextColor(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        // Remove just the color declaration from any inline style attribute.
        // Keep other style declarations intact.
        $pattern = '/\bcolor\s*:\s*(?:' .
            'black' .
            '|#000(?:000)?' .
            '|#111(?:111)?' .
            '|#222(?:222)?' .
            '|#333(?:333)?' .
            '|#111827' .
            '|#1f2937' .
            '|#374151' .
            '|rgb\(\s*0\s*,\s*0\s*,\s*0\s*\)' .
            '|rgb\(\s*17\s*,\s*24\s*,\s*39\s*\)' .
            '|rgb\(\s*31\s*,\s*41\s*,\s*55\s*\)' .
            '|rgb\(\s*55\s*,\s*65\s*,\s*81\s*\)' .
            '|rgba\(\s*0\s*,\s*0\s*,\s*0\s*,\s*1(?:\.0+)?\s*\)' .
            '|rgba\(\s*17\s*,\s*24\s*,\s*39\s*,\s*1(?:\.0+)?\s*\)' .
            '|rgba\(\s*31\s*,\s*41\s*,\s*55\s*,\s*1(?:\.0+)?\s*\)' .
            '|rgba\(\s*55\s*,\s*65\s*,\s*81\s*,\s*1(?:\.0+)?\s*\)' .
            ')\s*(?:!important\s*)?;?/i';

        $out = preg_replace($pattern, '', $html);
        if (! is_string($out)) {
            return $html;
        }

        // Cleanup: if we left empty style="  ;  " then remove style attribute.
        $out = preg_replace('/\sstyle="(?:\s*;?\s*)*"\s*/i', ' ', $out);

        return is_string($out) ? $out : $html;
    }

    /**
     * Normalize legacy Arabic HTML that contains excessive inline span styling.
     *
     * Goals:
     * - remove theme-breaking inline text colors / transparent backgrounds
     * - remove legacy font-family / font-size / white-space overrides
     * - preserve semantic structure (p, strong, em, headings, lists, quotes, links)
     * - provide a stable wrapper for RTL + typography styling
     */
    public static function normalizeLegacyArabicHtml(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $html = self::stripInlineBlackTextColor($html);

        if (! class_exists(\DOMDocument::class)) {
            // Fallback: best-effort strip common legacy declarations.
            $html = preg_replace('/\bbackground-color\s*:\s*transparent\s*(?:!important\s*)?;?/i', '', $html) ?: $html;
            $html = preg_replace('/\bfont-family\s*:\s*Arial\s*,\s*sans-serif\s*(?:!important\s*)?;?/i', '', $html) ?: $html;
            $html = preg_replace('/\bwhite-space\s*:\s*pre-wrap\s*(?:!important\s*)?;?/i', '', $html) ?: $html;
            $html = preg_replace('/\bfont-size\s*:\s*\d+(?:\.\d+)?pt\s*(?:!important\s*)?;?/i', '', $html) ?: $html;
            $html = preg_replace('/\sstyle="(?:\s*;?\s*)*"\s*/i', ' ', $html) ?: $html;

            return self::wrapArabicContent($html);
        }

        $prev = libxml_use_internal_errors(true);

        $doc = new \DOMDocument('1.0', 'UTF-8');
        // Use a wrapper div so we can reliably return inner HTML.
        $wrapped = '<div id="__nr_root__">' . $html . '</div>';
        $doc->loadHTML(
            mb_convert_encoding($wrapped, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $root = $doc->getElementById('__nr_root__');
        if (! $root) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            return self::wrapArabicContent($html);
        }

        $xpath = new \DOMXPath($doc);
        /** @var \DOMElement $el */
        foreach ($xpath->query('//*[@style]') as $el) {
            $style = $el->getAttribute('style');
            $normalized = self::normalizeStyleAttribute($style);

            if ($normalized === '') {
                $el->removeAttribute('style');
            } else {
                $el->setAttribute('style', $normalized);
            }
        }

        // Unwrap spans that became purely presentational (no attributes left).
        foreach ($xpath->query('//span[not(@*)]') as $span) {
            self::unwrapNode($span);
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return self::wrapArabicContent($out);
    }

    private static function wrapArabicContent(string $html): string
    {
        $trimmed = trim($html);
        if ($trimmed === '') {
            return $html;
        }

        // Avoid double wrapping.
        if (preg_match('/class="[^"]*\bnr-arabic-content\b[^"]*"/i', $trimmed)) {
            return $html;
        }

        return '<div lang="ar" dir="rtl" class="nr-arabic-content">' . $html . '</div>';
    }

    private static function normalizeStyleAttribute(string $style): string
    {
        $parts = preg_split('/\s*;\s*/', trim($style)) ?: [];
        $kept = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            [$prop, $value] = array_pad(explode(':', $part, 2), 2, '');
            $prop = strtolower(trim($prop));
            $valueRaw = trim($value);
            $valueLower = strtolower($valueRaw);

            if ($prop === 'color') {
                // Drop black/dark colors entirely; CSS will control theme colors.
                if (preg_match('/^(black|#000(?:000)?|#111(?:111)?|#222(?:222)?|#333(?:333)?|#111827|#1f2937|#374151)$/i', $valueLower)) {
                    continue;
                }
                if (preg_match('/^rgba?\(\s*(0|17|31|55)\s*,\s*(0|24|41|65)\s*,\s*(0|39|55|81)\s*(?:,\s*1(?:\.0+)?\s*)?\)$/i', $valueLower)) {
                    continue;
                }
            }

            if ($prop === 'background-color' && preg_match('/^transparent$/i', $valueLower)) {
                continue;
            }

            if ($prop === 'font-family' && str_contains($valueLower, 'arial')) {
                continue;
            }

            if ($prop === 'white-space' && preg_match('/^(pre-wrap|pre|nowrap)$/i', $valueLower)) {
                continue;
            }

            if ($prop === 'font-size' && preg_match('/^\d+(?:\.\d+)?pt$/i', $valueLower)) {
                continue;
            }

            // Preserve everything else (bold/italic/etc usually isn't in style anyway, but keep safe).
            $kept[] = $prop . ': ' . $valueRaw;
        }

        return implode('; ', $kept);
    }

    private static function unwrapNode(\DOMNode $node): void
    {
        $parent = $node->parentNode;
        if (! $parent) {
            return;
        }

        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }

        $parent->removeChild($node);
    }
}

