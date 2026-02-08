<?php

namespace App\Services\Religious;

use App\Contracts\HadithSearchServiceInterface;
use App\Contracts\QuranSearchServiceInterface;
use Illuminate\Support\Str;

/**
 * ReligiousReferenceRenderer Service
 * 
 * Parses HTML content for religious references (Quran Ayahs and Hadith Items)
 * and replaces them with formatted HTML for display.
 */
class ReligiousReferenceRenderer
{
    protected QuranSearchServiceInterface $quranService;
    protected HadithSearchServiceInterface $hadithService;

    public function __construct(
        QuranSearchServiceInterface $quranService,
        HadithSearchServiceInterface $hadithService
    ) {
        $this->quranService = $quranService;
        $this->hadithService = $hadithService;
    }

    /**
     * Render HTML content with religious references formatted.
     * 
     * @param string $html The HTML content containing references
     * @param string $format Format style: 'inline' or 'blockquote'
     * @return string Rendered HTML
     */
    public function render(string $html, string $format = 'inline'): string
    {
        if (empty($html)) {
            return '';
        }

        // Parse structured HTML nodes: <span data-ref="ayah" data-id="123">[...]</span>
        $html = $this->renderStructuredNodes($html, $format);

        // Also parse shortcodes: [ayah:123] or [hadith:456]
        $html = $this->renderShortcodes($html, $format);

        return $html;
    }

    /**
     * Render structured HTML nodes.
     */
    protected function renderStructuredNodes(string $html, string $format): string
    {
        // Match: <span data-ref="ayah" data-id="123">[...]</span>
        $pattern = '/<span\s+data-ref="(ayah|hadith)"\s+data-id="(\d+)"[^>]*>\[([^\]]+)\]<\/span>/i';
        
        return preg_replace_callback($pattern, function ($matches) use ($format) {
            $type = $matches[1];
            $id = (int) $matches[2];
            $label = $matches[3];

            return $this->formatReference($type, $id, $label, $format);
        }, $html);
    }

    /**
     * Render shortcode format: [ayah:123] or [hadith:456]
     */
    protected function renderShortcodes(string $html, string $format): string
    {
        // Match: [ayah:123] or [hadith:456]
        $pattern = '/\[(ayah|hadith):(\d+)\]/i';
        
        return preg_replace_callback($pattern, function ($matches) use ($format) {
            $type = $matches[1];
            $id = (int) $matches[2];

            // Get label from service
            $label = $this->getReferenceLabel($type, $id);
            
            return $this->formatReference($type, $id, $label, $format);
        }, $html);
    }

    /**
     * Get reference label from service.
     */
    protected function getReferenceLabel(string $type, int $id): string
    {
        try {
            if ($type === 'ayah') {
                $labels = $this->quranService->getVerseLabels([$id]);
                return $labels[$id] ?? "Quran Verse #{$id}";
            } elseif ($type === 'hadith') {
                $labels = $this->hadithService->getHadithLabels([$id]);
                return $labels[$id] ?? "Hadith #{$id}";
            }
        } catch (\Exception $e) {
            // Fallback if service fails
        }

        return $type === 'ayah' ? "Quran Verse #{$id}" : "Hadith #{$id}";
    }

    /**
     * Format a reference for display.
     */
    protected function formatReference(string $type, int $id, string $label, string $format): string
    {
        $cssClass = $type === 'ayah' ? 'religious-reference-ayah' : 'religious-reference-hadith';
        $icon = $type === 'ayah' ? '📖' : '💬';

        if ($format === 'blockquote') {
            return sprintf(
                '<blockquote class="%s" data-ref="%s" data-id="%d">
                    <div class="reference-header">%s %s</div>
                    <div class="reference-content">%s</div>
                </blockquote>',
                $cssClass,
                $type,
                $id,
                $icon,
                $label,
                $this->getReferenceContent($type, $id)
            );
        }

        // Inline format
        return sprintf(
            '<span class="%s" data-ref="%s" data-id="%d" title="%s">%s %s</span>',
            $cssClass,
            $type,
            $id,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
            $icon,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Get reference content (Arabic text preview).
     */
    protected function getReferenceContent(string $type, int $id): string
    {
        // For now, return empty. Can be extended to fetch full text if needed.
        return '';
    }

    /**
     * Extract reference IDs from HTML content.
     * 
     * @param string $html The HTML content
     * @return array ['ayah' => [1, 2, 3], 'hadith' => [4, 5]]
     */
    public function extractReferenceIds(string $html): array
    {
        $references = [
            'ayah' => [],
            'hadith' => [],
        ];

        if (empty($html)) {
            return $references;
        }

        // Extract from structured nodes
        $pattern = '/<span\s+data-ref="(ayah|hadith)"\s+data-id="(\d+)"[^>]*>/i';
        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $type = $match[1];
            $id = (int) $match[2];
            if (!in_array($id, $references[$type])) {
                $references[$type][] = $id;
            }
        }

        // Extract from shortcodes
        $shortcodePattern = '/\[(ayah|hadith):(\d+)\]/i';
        preg_match_all($shortcodePattern, $html, $shortcodeMatches, PREG_SET_ORDER);
        
        foreach ($shortcodeMatches as $match) {
            $type = $match[1];
            $id = (int) $match[2];
            if (!in_array($id, $references[$type])) {
                $references[$type][] = $id;
            }
        }

        return $references;
    }
}
