<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentToolSelection;
use App\Models\KeyBehavior;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Cakupan kompetensi wajib dan PK yang layak integrasi / finalisasi.
 */
class MandatoryCompetencyCoverage
{
    /**
     * PK yang layak masuk integrasi pratinjau dan menghitung cakupan finalisasi.
     */
    public static function queryPkLayak(Assessment $asesmen): Builder
    {
        $idAlatAktif = self::idAlatAktif($asesmen);
        $pasanganLayak = self::pasanganPemetaanLayak($asesmen, $idAlatAktif);

        if ($pasanganLayak === []) {
            return KeyBehavior::query()->whereRaw('0 = 1');
        }

        return KeyBehavior::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('tervalidasi', true)
            ->whereNotNull('id_tingkat_kompetensi')
            ->where(function (Builder $query) use ($pasanganLayak): void {
                foreach ($pasanganLayak as $pasangan) {
                    $query->orWhere(function (Builder $q) use ($pasangan): void {
                        $q->where('id_kompetensi', $pasangan['id_kompetensi'])
                            ->where('id_alat_penilaian', $pasangan['id_alat_penilaian']);
                    });
                }
            });
    }

    /**
     * @return Collection<int, int>
     */
    public static function idKompetensiWajib(Assessment $asesmen): Collection
    {
        $idAlatAktif = self::idAlatAktif($asesmen);
        if ($idAlatAktif === []) {
            return collect();
        }

        return AssessmentMatrix::mappingQuery($asesmen)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where(function (Builder $query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique()
            ->values();
    }

    /**
     * @return array{
     *   total_wajib:int,
     *   total_terpenuhi:int,
     *   total_kompetensi_kurang:int,
     *   kompetensi_kurang: array<int, array{kode:string, nama:string}>
     * }
     */
    public static function ringkasan(Assessment $asesmen): array
    {
        $idKompetensiWajib = self::idKompetensiWajib($asesmen);
        if ($idKompetensiWajib->isEmpty()) {
            return [
                'total_wajib' => 0,
                'total_terpenuhi' => 0,
                'total_kompetensi_kurang' => 0,
                'kompetensi_kurang' => [],
            ];
        }

        $idTerpenuhi = self::queryPkLayak($asesmen)
            ->whereIn('id_kompetensi', $idKompetensiWajib->all())
            ->pluck('id_kompetensi')
            ->unique()
            ->values();

        $idKurang = $idKompetensiWajib->diff($idTerpenuhi)->values();

        $mapWajib = AssessmentMatrix::mappingQuery($asesmen)
            ->whereIn('id_kompetensi', $idKurang->all())
            ->where('wajib', true)
            ->with('competency')
            ->get();

        $kompetensiKurang = $mapWajib
            ->filter(fn (Model $m): bool => $idKurang->contains($m->id_kompetensi))
            ->map(fn (Model $m): array => [
                'kode' => (string) ($m->competency?->kode_kompetensi ?? '-'),
                'nama' => (string) ($m->competency?->nama ?? 'Kompetensi'),
            ])
            ->unique('kode')
            ->sortBy('kode')
            ->values()
            ->all();

        return [
            'total_wajib' => $idKompetensiWajib->count(),
            'total_terpenuhi' => $idTerpenuhi->count(),
            'total_kompetensi_kurang' => $idKurang->count(),
            'kompetensi_kurang' => $kompetensiKurang,
        ];
    }

    /**
     * @return list<int>
     */
    public static function idAlatAktif(Assessment $asesmen): array
    {
        return AssessmentToolSelection::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('aktif', true)
            ->pluck('id_alat_penilaian')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<array{id_kompetensi:int, id_alat_penilaian:int}>
     */
    public static function pasanganPemetaanLayak(Assessment $asesmen, ?array $idAlatAktif = null): array
    {
        $idAlatAktif ??= self::idAlatAktif($asesmen);
        if ($idAlatAktif === []) {
            return [];
        }

        return AssessmentMatrix::mappingQuery($asesmen)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where(function (Builder $query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->get(['id_kompetensi', 'id_alat_penilaian'])
            ->map(fn (Model $m): array => [
                'id_kompetensi' => (int) $m->id_kompetensi,
                'id_alat_penilaian' => (int) $m->id_alat_penilaian,
            ])
            ->all();
    }

    /**
     * @return Collection<int, CompetencyToolMapping> keyed by "kompetensi-alat"
     */
    public static function pemetaanTerindeks(Assessment $asesmen): Collection
    {
        $idAlatAktif = self::idAlatAktif($asesmen);
        if ($idAlatAktif === []) {
            return collect();
        }

        return AssessmentMatrix::mappingQuery($asesmen)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where(function (Builder $query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->with(['competency', 'tool'])
            ->get()
            ->keyBy(fn (Model $m): string => $m->id_kompetensi.'-'.$m->id_alat_penilaian);
    }
}
