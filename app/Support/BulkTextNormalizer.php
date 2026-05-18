<?php

namespace App\Support;

/**
 * Normalisasi teks muatan bulk: hanya tanda baca/unicode yang memicu mismatch kutipan.
 * Tidak mengubah spasi, baris baru, atau isi kata.
 */
final class BulkTextNormalizer
{
    /**
     * Normalisasi untuk penyimpanan dan teks yang dikirim ke model AI.
     */
    public static function normalizeForStorage(string $text): string
    {
        return self::normalizePunctuation(self::normalizeParagraphBreaks($text));
    }

    /**
     * @alias normalizeForStorage
     */
    public static function normalizeForMatch(string $text): string
    {
        return self::normalizeForStorage($text);
    }

    /**
     * Pergantian paragraf diseragamkan menjadi satu baris kosong (\n\n).
     */
    public static function normalizeParagraphBreaks(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return $text;
    }

    public static function kutipanCocok(string $haystack, string $kutipan): bool
    {
        if ($kutipan === '') {
            return false;
        }

        if (str_contains($haystack, $kutipan)) {
            return true;
        }

        return self::findVerbatimSubstring($haystack, $kutipan) !== null;
    }

    /**
     * Ambil substring asli dari haystack yang setara dengan kutipan setelah normalisasi tanda baca.
     */
    public static function findVerbatimSubstring(string $haystack, string $kutipan): ?string
    {
        if ($kutipan === '') {
            return null;
        }

        if (str_contains($haystack, $kutipan)) {
            return $kutipan;
        }

        $normKutipan = self::normalizeForMatch($kutipan);
        if ($normKutipan === '') {
            return null;
        }

        $hayLen = mb_strlen($haystack);
        for ($start = 0; $start < $hayLen; $start++) {
            for ($end = $start + 1; $end <= $hayLen; $end++) {
                $potongan = mb_substr($haystack, $start, $end - $start);
                if (self::normalizeForMatch($potongan) === $normKutipan) {
                    return $potongan;
                }
                $normPotongan = self::normalizeForMatch($potongan);
                if (mb_strlen($normPotongan) > mb_strlen($normKutipan)) {
                    break;
                }
            }
        }

        return null;
    }

    /**
     * Coba beberapa sumber teks muatan (normalized / mentah).
     *
     * @param  list<string>  $sumberTeks
     * @return array{0: string, 1: string}|null  [teks_sumber, kutipan_verbatim]
     */
    public static function selesaikanKutipan(array $sumberTeks, string $kutipan): ?array
    {
        foreach ($sumberTeks as $teks) {
            $teks = trim($teks);
            if ($teks === '') {
                continue;
            }
            $verbatim = self::findVerbatimSubstring($teks, $kutipan);
            if ($verbatim !== null) {
                return [$teks, $verbatim];
            }
        }

        return null;
    }

    private static function normalizePunctuation(string $text): string
    {
        if ($text === '') {
            return '';
        }

        $text = str_replace("\u{00A0}", ' ', $text);

        $hapus = [
            "\u{200B}", "\u{200C}", "\u{200D}", "\u{FEFF}", "\u{00AD}",
        ];
        $text = str_replace($hapus, '', $text);

        $ganti = [
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{201E}" => '"',
            "\u{00AB}" => '"',
            "\u{00BB}" => '"',
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201A}" => "'",
            "\u{2032}" => "'",
            "\u{2013}" => '-',
            "\u{2014}" => '-',
            "\u{2212}" => '-',
            "\u{2026}" => '...',
        ];

        return strtr($text, $ganti);
    }
}
