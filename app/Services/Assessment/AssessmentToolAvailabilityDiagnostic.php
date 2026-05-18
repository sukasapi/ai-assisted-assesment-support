<?php

namespace App\Services\Assessment;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\AssessmentToolSelection;
use App\Models\CompetencyToolMapping;
use Illuminate\Support\Collection;

/**
 * Diagnostik ketersediaan alat untuk input asesmen (preset ∩ pemetaan matriks).
 */
final class AssessmentToolAvailabilityDiagnostic
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Assessment $asesmen): array
    {
        $asesmen->loadMissing(['matrixVersion', 'toolSelections.tool']);

        $pemetaan = self::pemetaanUntukVersi($asesmen->id_versi_matriks);
        $idAlatDiMatriks = $pemetaan
            ->pluck('id_alat_penilaian')
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $pemilihanUrut = self::urutkanPemilihan($asesmen->toolSelections);
        $alatTersediaInput = self::filterAlatTersediaInput($pemilihanUrut, $idAlatDiMatriks);

        $alatDiMatriks = $pemetaan
            ->unique('id_alat_penilaian')
            ->sortBy(fn (CompetencyToolMapping $m): array => self::urutanAlat($m->tool))
            ->values()
            ->map(fn (CompetencyToolMapping $m): array => self::formatAlat($m->tool, (int) $m->id_alat_penilaian))
            ->values()
            ->all();

        $pemilihanPreset = $pemilihanUrut
            ->map(function (AssessmentToolSelection $sel) use ($idAlatDiMatriks): array {
                $idAlat = (int) $sel->id_alat_penilaian;

                return array_merge(self::formatPemilihan($sel), [
                    'ada_di_matriks' => in_array($idAlat, $idAlatDiMatriks, true),
                ]);
            })
            ->values()
            ->all();

        $pemilihanTanpaPemetaan = collect($pemilihanPreset)
            ->filter(fn (array $row): bool => $row['aktif'] && ! $row['ada_di_matriks'])
            ->values()
            ->all();

        return [
            'id_asesmen' => $asesmen->id,
            'id_versi_matriks' => $asesmen->id_versi_matriks,
            'kode_versi_matriks' => $asesmen->matrixVersion?->kode_versi,
            'nama_versi_matriks' => $asesmen->matrixVersion?->nama_versi,
            'metode_koleksi_bukti' => $asesmen->metode_koleksi_bukti?->value,
            'ringkasan_matriks' => [
                'jumlah_pemetaan' => $pemetaan->count(),
                'jumlah_alat_unik' => count($idAlatDiMatriks),
            ],
            'alat_di_matriks' => $alatDiMatriks,
            'pemilihan_preset' => $pemilihanPreset,
            'alat_tersedia_input' => $alatTersediaInput
                ->map(fn (AssessmentToolSelection $sel): array => self::formatPemilihan($sel))
                ->values()
                ->all(),
            'pemilihan_tanpa_pemetaan' => $pemilihanTanpaPemetaan,
            'punya_alat_tersedia' => $alatTersediaInput->isNotEmpty(),
        ];
    }

    /**
     * Koleksi yang dipakai halaman detail asesmen (show).
     *
     * @return array{
     *     pemetaanKompetensiAlat: Collection<int, CompetencyToolMapping>,
     *     alatTersediaInput: Collection<int, AssessmentToolSelection>
     * }
     */
    public static function collectionsForShow(Assessment $asesmen): array
    {
        $asesmen->loadMissing(['toolSelections.tool']);

        $pemetaan = self::pemetaanUntukVersi($asesmen->id_versi_matriks)
            ->keyBy(fn (CompetencyToolMapping $m) => $m->id_kompetensi.'-'.$m->id_alat_penilaian);

        $idAlatDiMatriks = $pemetaan
            ->pluck('id_alat_penilaian')
            ->unique()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        $alatTersediaInput = self::filterAlatTersediaInput(
            self::urutkanPemilihan($asesmen->toolSelections),
            $idAlatDiMatriks,
        );

        return [
            'pemetaanKompetensiAlat' => $pemetaan,
            'alatTersediaInput' => $alatTersediaInput,
        ];
    }

    /**
     * @return Collection<int, CompetencyToolMapping>
     */
    private static function pemetaanUntukVersi(int $idVersiMatriks): Collection
    {
        return CompetencyToolMapping::query()
            ->where('id_versi_matriks', $idVersiMatriks)
            ->where(function ($query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->get();
    }

    /**
     * @param  Collection<int, AssessmentToolSelection>  $pemilihan
     * @param  list<int>  $idAlatDiMatriks
     * @return Collection<int, AssessmentToolSelection>
     */
    private static function filterAlatTersediaInput(Collection $pemilihan, array $idAlatDiMatriks): Collection
    {
        return $pemilihan
            ->where('aktif', true)
            ->filter(fn (AssessmentToolSelection $sel): bool => in_array((int) $sel->id_alat_penilaian, $idAlatDiMatriks, true))
            ->values();
    }

    /**
     * @param  Collection<int, AssessmentToolSelection>  $pemilihan
     * @return Collection<int, AssessmentToolSelection>
     */
    private static function urutkanPemilihan(Collection $pemilihan): Collection
    {
        return $pemilihan
            ->sortBy(function (AssessmentToolSelection $s): array {
                return self::urutanAlat($s->tool);
            })
            ->values();
    }

    /**
     * @param  AssessmentTool|null  $tool
     * @return array{0: int, 1: string}
     */
    private static function urutanAlat($tool): array
    {
        return [(int) ($tool->urutan ?? 9999), $tool->kode ?? ''];
    }

    /**
     * @return array<string, mixed>
     */
    private static function formatPemilihan(AssessmentToolSelection $sel): array
    {
        return [
            'id_pemilihan' => $sel->id,
            'id_alat_penilaian' => (int) $sel->id_alat_penilaian,
            'kode' => $sel->tool?->kode,
            'nama' => $sel->tool?->nama,
            'aktif' => (bool) $sel->aktif,
            'wajib' => (bool) $sel->wajib,
        ];
    }

    /**
     * @param  AssessmentTool|null  $tool
     * @return array<string, mixed>
     */
    private static function formatAlat($tool, int $idAlatPenilaian): array
    {
        return [
            'id_alat_penilaian' => $idAlatPenilaian,
            'kode' => $tool?->kode,
            'nama' => $tool?->nama,
        ];
    }
}
