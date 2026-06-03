<?php

namespace App\Support;

use App\Models\AiPromptTemplate;
use App\Models\Assessment;

/**
 * Menggabungkan prompt sistem bawaan dengan instruksi template (per alat).
 */
final class AiPromptComposer
{
    public static function gabungkanSystem(string $promptDasar, ?Assessment $asesmen, ?int $idAlatPenilaian = null): string
    {
        $tambahan = $idAlatPenilaian !== null && $idAlatPenilaian > 0
            ? AiPromptTemplateResolver::teksInstruksiUntukAlat($asesmen, $idAlatPenilaian)
            : self::teksInstruksiLegacyAsesmen($asesmen);

        if ($tambahan === '') {
            return $promptDasar;
        }

        return $promptDasar."\n\n"
            ."--- Kerangka analisis tambahan (template) ---\n"
            .$tambahan;
    }

    /**
     * @deprecated gunakan metadataUntukAlat; dipertahankan untuk kompatibilitas log tanpa id alat.
     *
     * @return array{kode: string|null, nama: string|null}
     */
    public static function metadataTemplate(?Assessment $asesmen): array
    {
        if ($asesmen === null) {
            return ['kode' => null, 'nama' => null];
        }

        if (! $asesmen->relationLoaded('aiPromptTemplate')) {
            $asesmen->loadMissing('aiPromptTemplate');
        }

        $template = $asesmen->aiPromptTemplate;
        if ($template === null) {
            return ['kode' => null, 'nama' => null];
        }

        return [
            'kode' => $template->kode,
            'nama' => $template->nama,
        ];
    }

    /**
     * @return array{kode: string|null, nama: string|null, sumber: string|null}
     */
    public static function metadataUntukAlat(?Assessment $asesmen, ?int $idAlatPenilaian): array
    {
        return AiPromptTemplateResolver::metadataUntukAlat($asesmen, $idAlatPenilaian);
    }

    /**
     * @return list<array{id: int, kode: string, nama: string, deskripsi: string|null}>
     */
    public static function opsiTemplateAktif(): array
    {
        return AiPromptTemplate::query()
            ->where('aktif', true)
            ->whereNull('dihapus_pada')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'deskripsi'])
            ->map(static fn (AiPromptTemplate $t): array => [
                'id' => $t->id,
                'kode' => $t->kode,
                'nama' => $t->nama,
                'deskripsi' => $t->deskripsi,
            ])
            ->all();
    }

    private static function teksInstruksiLegacyAsesmen(?Assessment $asesmen): string
    {
        if ($asesmen === null) {
            return '';
        }

        if (! $asesmen->relationLoaded('aiPromptTemplate')) {
            $asesmen->loadMissing('aiPromptTemplate');
        }

        $template = $asesmen->aiPromptTemplate;
        if ($template === null || ! $template->aktif) {
            return '';
        }

        return trim((string) $template->teks_instruksi);
    }
}
