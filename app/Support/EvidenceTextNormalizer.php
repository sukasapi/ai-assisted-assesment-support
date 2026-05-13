<?php

namespace App\Support;

final class EvidenceTextNormalizer
{
    /**
     * Normalisasi HTML/teks ke plain text seragam.
     */
    public static function normalize(?string $rich, ?string $fallbackPlain = null): string
    {
        $rich = trim((string) $rich);
        if ($rich === '') {
            return self::normalizePlain((string) $fallbackPlain);
        }
        if (! str_contains($rich, '<')) {
            return self::normalizePlain($rich);
        }

        $html = mb_convert_encoding($rich, 'HTML-ENTITIES', 'UTF-8');
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'.$html);
        libxml_clear_errors();

        $tables = $dom->getElementsByTagName('table');
        while ($tables->length > 0) {
            $table = $tables->item(0);
            if (! $table instanceof \DOMElement || ! $table->parentNode) {
                break;
            }
            $rows = [];
            foreach ($table->getElementsByTagName('tr') as $tr) {
                $cells = [];
                foreach ($tr->childNodes as $cell) {
                    if (! $cell instanceof \DOMElement) {
                        continue;
                    }
                    if (! in_array(strtolower($cell->tagName), ['th', 'td'], true)) {
                        continue;
                    }
                    $cells[] = self::normalizePlain($cell->textContent ?? '');
                }
                if ($cells !== []) {
                    $rows[] = implode(' | ', $cells);
                }
            }
            $replacement = $dom->createTextNode("\n".implode("\n", $rows)."\n");
            $table->parentNode->replaceChild($replacement, $table);
        }

        $plain = html_entity_decode(strip_tags($dom->saveHTML() ?: ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return self::normalizePlain($plain);
    }

    public static function normalizePlain(string $text): string
    {
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
