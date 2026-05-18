<?php

namespace App\Services\Ai;

/**
 * Parser respons teks/JSON dari model LLM (markdown, substring, variasi kunci).
 */
class AiModelJsonParser
{
    /**
     * @return array<string, mixed>|null
     */
    public static function parseObjek(string $teks): ?array
    {
        $trim = trim($teks);
        if ($trim === '') {
            return null;
        }

        $candidates = [$trim];
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $trim, $cocok)) {
            $candidates[] = trim($cocok[1]);
        }

        $potongan = self::potonganJsonTerluar($trim);
        if ($potongan !== null && $potongan !== $trim) {
            $candidates[] = $potongan;
        }

        foreach (array_unique($candidates) as $kandidat) {
            $decoded = self::decodeArray($kandidat);
            if ($decoded !== null) {
                return $decoded;
            }
            $potong = self::potonganJsonTerluar($kandidat);
            if ($potong !== null && $potong !== $kandidat) {
                $decoded = self::decodeArray($potong);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Ambil array usulan bulk dari berbagai bentuk respons model.
     *
     * @param  array<string, mixed>|list<mixed>  $objek
     * @return list<mixed>|null
     */
    public static function ekstrakArrayUsulanBulk(array $objek): ?array
    {
        if (isset($objek['usulan']) && is_array($objek['usulan'])) {
            return $objek['usulan'];
        }

        foreach (['Usulan', 'usulan_bulk', 'proposals', 'suggestions', 'data', 'items', 'hasil'] as $kunci) {
            if (isset($objek[$kunci]) && is_array($objek[$kunci])) {
                return $objek[$kunci];
            }
        }

        if ($objek !== [] && array_is_list($objek)) {
            $pertama = $objek[0] ?? null;
            if (is_array($pertama) && (array_key_exists('kode_kompetensi', $pertama) || array_key_exists('kutipan', $pertama))) {
                return $objek;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeArray(string $json): ?array
    {
        try {
            $decoded = json_decode(trim($json), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private static function potonganJsonTerluar(string $teks): ?string
    {
        $teks = trim($teks);
        $posObj = strpos($teks, '{');
        $posArr = strpos($teks, '[');

        if ($posObj === false && $posArr === false) {
            return null;
        }

        if ($posObj === false || ($posArr !== false && $posArr < $posObj)) {
            $mulai = $posArr;
            $akhir = strrpos($teks, ']');
        } else {
            $mulai = $posObj;
            $akhir = strrpos($teks, '}');
        }

        if ($akhir === false || $akhir < $mulai) {
            return null;
        }

        return substr($teks, $mulai, $akhir - $mulai + 1);
    }
}
