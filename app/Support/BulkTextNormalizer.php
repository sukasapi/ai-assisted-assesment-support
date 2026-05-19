<?php

namespace App\Support;

/**
 * Normalisasi teks muatan bulk: hanya tanda baca/unicode yang memicu mismatch kutipan.
 * Tidak mengubah spasi, baris baru, atau isi kata (kecuali saat pencocokan kutipan — lihat normalizeWhitespaceForMatch).
 */
final class BulkTextNormalizer
{
    /**
     * Teks kanonik untuk penyimpanan payload dan analisis AI (satu sumber kebenaran).
     */
    public static function canonicalPayloadMuatan(?string $rich, ?string $plain, ?string $fallback = null): string
    {
        $plain = EvidenceTextNormalizer::toPlainText($rich, $plain ?? $fallback);
        $sumber = $plain !== '' ? $plain : trim((string) $fallback);

        return self::normalizeForStorage($sumber);
    }

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
        $kutipan = trim($kutipan);
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

        $ditemukan = self::cariPotonganSetara($haystack, $normKutipan, false);
        if ($ditemukan !== null) {
            return $ditemukan;
        }

        $normKutipanSpasi = self::normalizeWhitespaceForMatch($kutipan);
        if ($normKutipanSpasi !== '' && $normKutipanSpasi !== $normKutipan) {
            return self::cariPotonganSetara($haystack, $normKutipanSpasi, true);
        }

        return null;
    }

    /**
     * Cari potongan asli di haystack yang setara dengan kutipan ter-normalisasi (O(n·Δ) bukan O(n²)).
     */
    private static function cariPotonganSetara(string $haystack, string $normKutipan, bool $whitespaceTolerant): ?string
    {
        $kLen = mb_strlen($normKutipan);
        if ($kLen === 0) {
            return null;
        }

        $hayLen = mb_strlen($haystack);
        $minLen = max(1, $kLen - 4);
        $maxLen = min($hayLen, $kLen + (int) ceil($kLen * 0.15) + 8);

        for ($start = 0; $start < $hayLen; $start++) {
            $batasLen = min($maxLen, $hayLen - $start);
            for ($len = $minLen; $len <= $batasLen; $len++) {
                $potongan = mb_substr($haystack, $start, $len);
                $normPotongan = $whitespaceTolerant
                    ? self::normalizeWhitespaceForMatch($potongan)
                    : self::normalizeForMatch($potongan);

                if ($normPotongan === $normKutipan) {
                    return $potongan;
                }

                if (mb_strlen($normPotongan) > $kLen + 2) {
                    break;
                }
            }
        }

        return null;
    }

    /**
     * Normalisasi tambahan hanya untuk pencocokan kutipan (model sering meratakan spasi ganda).
     */
    public static function normalizeWhitespaceForMatch(string $text): string
    {
        $text = self::normalizeForMatch($text);

        return preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
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
