<?php

namespace App\Support;

final class EvidenceTextNormalizer
{
    /**
     * Ekstrak plain text dari HTML tanpa mengecilkan spasi/baris (untuk bulk).
     */
    public static function toPlainText(?string $rich, ?string $fallbackPlain = null): string
    {
        $rich = trim((string) $rich);
        if ($rich === '') {
            return BulkTextNormalizer::normalizeParagraphBreaks(
                str_replace("\u{00A0}", ' ', (string) $fallbackPlain)
            );
        }
        if (! str_contains($rich, '<')) {
            return BulkTextNormalizer::normalizeParagraphBreaks(
                str_replace("\u{00A0}", ' ', $rich)
            );
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
                    $cells[] = str_replace("\u{00A0}", ' ', $cell->textContent ?? '');
                }
                if ($cells !== []) {
                    $rows[] = implode(' | ', $cells);
                }
            }
            $replacement = $dom->createTextNode("\n".implode("\n", $rows)."\n");
            $table->parentNode->replaceChild($replacement, $table);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $plain = $body instanceof \DOMElement
            ? self::domNodeToPlainText($body)
            : html_entity_decode(strip_tags($dom->saveHTML() ?: ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return BulkTextNormalizer::normalizeParagraphBreaks(
            str_replace("\u{00A0}", ' ', $plain)
        );
    }

    /**
     * Blok HTML (p, div, li, …) → akhir paragraf dengan \n\n.
     */
    private static function domNodeToPlainText(\DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return str_replace("\u{00A0}", ' ', $node->textContent ?? '');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        if (! $node instanceof \DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'br') {
            return "\n";
        }

        $isBlock = in_array($tag, [
            'p', 'div', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'pre', 'tr', 'section', 'article',
        ], true);

        $buf = '';
        foreach ($node->childNodes as $child) {
            $buf .= self::domNodeToPlainText($child);
        }

        if ($isBlock) {
            $trimmed = rtrim($buf, "\n");

            return $trimmed === '' ? "\n\n" : $trimmed."\n\n";
        }

        return $buf;
    }

    /**
     * Normalisasi HTML/teks ke plain text seragam (bukti penilaian).
     */
    public static function normalize(?string $rich, ?string $fallbackPlain = null): string
    {
        return self::normalizePlain(self::toPlainText($rich, $fallbackPlain));
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
