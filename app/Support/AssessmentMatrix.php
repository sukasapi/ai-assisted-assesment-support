<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentMatrixMappingSnapshot;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixCompetencyTarget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sumber kebenaran pemetaan kompetensi–alat untuk sebuah asesmen.
 *
 * Bila asesmen punya snapshot (dibekukan saat dibuat), seluruh scoring/validasi membaca
 * dari snapshot itu sehingga perubahan matriks "hidup" tidak menggeser hasil asesmen lama.
 * Asesmen lama (sebelum fitur snapshot) otomatis fallback ke matriks hidup.
 */
class AssessmentMatrix
{
    /** @var array<int, bool> memo per-request: id_asesmen => punya snapshot */
    private static array $memoPunyaSnapshot = [];

    /**
     * Bekukan pemetaan matriks aktif ke snapshot asesmen (idempoten: tulis ulang penuh).
     */
    public static function snapshot(Assessment $asesmen): void
    {
        $sumber = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->get(['id_kompetensi', 'id_alat_penilaian', 'wajib', 'bobot', 'aktif']);

        // Target jabatan per kompetensi (bila didefinisikan) — dibekukan bersama pemetaan.
        $targetPerKompetensi = MatrixCompetencyTarget::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->pluck('tingkat_target', 'id_kompetensi');

        AssessmentMatrixMappingSnapshot::query()->where('id_asesmen', $asesmen->id)->delete();

        $sekarang = now();
        $baris = $sumber->map(fn (CompetencyToolMapping $m): array => [
            'id_asesmen' => $asesmen->id,
            'id_kompetensi' => (int) $m->id_kompetensi,
            'id_alat_penilaian' => (int) $m->id_alat_penilaian,
            'wajib' => (bool) $m->wajib,
            'bobot' => $m->bobot,
            'tingkat_target' => $targetPerKompetensi[$m->id_kompetensi] ?? null,
            'aktif' => $m->aktif === null ? null : (bool) $m->aktif,
            'dibuat_pada' => $sekarang,
            'diperbarui_pada' => $sekarang,
        ])->all();

        if ($baris !== []) {
            AssessmentMatrixMappingSnapshot::query()->insert($baris);
        }

        unset(self::$memoPunyaSnapshot[$asesmen->id]);
    }

    public static function punyaSnapshot(Assessment $asesmen): bool
    {
        return self::$memoPunyaSnapshot[$asesmen->id] ??= AssessmentMatrixMappingSnapshot::query()
            ->where('id_asesmen', $asesmen->id)
            ->exists();
    }

    /**
     * Builder pemetaan efektif untuk asesmen: snapshot bila ada, jika tidak matriks hidup.
     * Kolom & relasi (competency, tool, wajib, bobot, aktif) identik di kedua model.
     */
    public static function mappingQuery(Assessment $asesmen): Builder
    {
        if (self::punyaSnapshot($asesmen)) {
            return AssessmentMatrixMappingSnapshot::query()->where('id_asesmen', $asesmen->id);
        }

        return CompetencyToolMapping::query()->where('id_versi_matriks', $asesmen->id_versi_matriks);
    }
}
