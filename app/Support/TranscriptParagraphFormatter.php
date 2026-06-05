<?php

namespace App\Support;

class TranscriptParagraphFormatter
{
    /**
     * Gabungkan segmen STT; sisipkan paragraf baru bila jeda antar segmen >= ambang.
     *
     * @param  list<array{start: float, end: float, text: string}>  $segments
     */
    public static function dariSegmen(array $segments, float $gapDetik): string
    {
        if ($segments === []) {
            return '';
        }

        $bagian = [];
        $sebelumnya = null;

        foreach ($segments as $seg) {
            $teks = trim((string) ($seg['text'] ?? ''));
            if ($teks === '') {
                continue;
            }

            if ($sebelumnya !== null) {
                $jeda = (float) ($seg['start'] ?? 0) - (float) ($sebelumnya['end'] ?? 0);
                if ($jeda >= $gapDetik) {
                    $bagian[] = "\n\n";
                } else {
                    $bagian[] = ' ';
                }
            }

            $bagian[] = $teks;
            $sebelumnya = $seg;
        }

        return trim(implode('', $bagian));
    }

    /**
     * Fallback bila API hanya mengembalikan teks utuh tanpa segmen.
     */
    public static function dariTeksUtuh(string $teks): string
    {
        $teks = trim($teks);
        if ($teks === '') {
            return '';
        }

        if (str_contains($teks, "\n\n")) {
            return $teks;
        }

        $kalimat = preg_split('/(?<=[.!?])\s+/u', $teks, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($kalimat) || count($kalimat) <= 3) {
            return $teks;
        }

        $paragraf = [];
        $buffer = [];
        foreach ($kalimat as $i => $kal) {
            $buffer[] = trim($kal);
            if (count($buffer) >= 3 || $i === count($kalimat) - 1) {
                $paragraf[] = implode(' ', $buffer);
                $buffer = [];
            }
        }

        return implode("\n\n", $paragraf);
    }
}
