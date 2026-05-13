<?php

namespace App\Services\Assessment;

use App\Enums\AssessmentPurpose;
use App\Models\AssessmentTool;
use App\Models\AssessmentToolSelection;
use App\Models\CompetencyToolMapping;

/**
 * Preset alat berdasarkan tujuan asesmen (wawancara) + opsi tanpa INTRAY (BOD-3).
 * Promosi: PA, INTRAY, LGD, RA, MI, BEI — Pemetaan talenta: tanpa Rencana Aksi (RA).
 */
final class AlatAsesmenPreset
{
    /**
     * @return list<string> kode alat (mis. PA, INTRAY, BEI)
     */
    public static function kodeAlat(AssessmentPurpose $tujuan, bool $tanpaIntray): array
    {
        $promosi = ['PA', 'INTRAY', 'LGD', 'RA', 'MI', 'BEI'];
        $talenta = ['PA', 'INTRAY', 'LGD', 'MI', 'BEI'];

        $codes = $tujuan === AssessmentPurpose::Promosi ? $promosi : $talenta;

        if ($tanpaIntray) {
            $codes = array_values(array_filter($codes, fn (string $k) => $k !== 'INTRAY'));
        }

        return $codes;
    }

    /**
     * Isi tabel pemilihan alat untuk satu asesmen.
     */
    public static function buatPemilihan(int $idAsesmen, int $idVersiMatriks, AssessmentPurpose $tujuan, bool $tanpaIntray): void
    {
        $kodes = self::kodeAlat($tujuan, $tanpaIntray);

        foreach ($kodes as $kode) {
            $alat = AssessmentTool::query()->where('kode', $kode)->where('aktif', true)->first();
            if ($alat === null) {
                continue;
            }

            $wajib = CompetencyToolMapping::query()
                ->where('id_versi_matriks', $idVersiMatriks)
                ->where('id_alat_penilaian', $alat->id)
                ->where('wajib', true)
                ->where('aktif', true)
                ->exists();

            AssessmentToolSelection::query()->create([
                'id_asesmen' => $idAsesmen,
                'id_alat_penilaian' => $alat->id,
                'wajib' => $wajib,
                'aktif' => true,
            ]);
        }
    }
}
