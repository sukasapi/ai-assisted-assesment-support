<?php

namespace App\Support;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentSession;
use Illuminate\Support\Collection;

/**
 * Ringkasan agregat satu sesi assessment: status, Job Fit, distribusi rekomendasi, peringkat peserta.
 */
class SessionAggregate
{
    /**
     * @return array{
     *   total:int, draf:int, terintegrasi:int, final:int,
     *   dinilai:int, belum_dinilai:int,
     *   rata_job_fit: float|null, job_fit_tertinggi: float|null, job_fit_terendah: float|null,
     *   qualified:int, not_qualified:int, belum_rekomendasi:int,
     *   peringkat: Collection<int, Assessment>
     * }
     */
    public static function untukSesi(AssessmentSession $sesi): array
    {
        /** @var Collection<int, Assessment> $asesmen */
        $asesmen = $sesi->assessments;

        $total = $asesmen->count();
        $draf = $asesmen->where('status', AssessmentStatus::Draf)->count();
        $terintegrasi = $asesmen->where('status', AssessmentStatus::Terintegrasi)->count();
        $final = $asesmen->where('status', AssessmentStatus::SelesaiFinal)->count();

        $denganJobFit = $asesmen->filter(fn (Assessment $a): bool => $a->job_fit_persen_pratinjau !== null);
        $nilaiJobFit = $denganJobFit->map(fn (Assessment $a): float => (float) $a->job_fit_persen_pratinjau);

        $qualified = $asesmen->where('kode_rekomendasi_agregat', 'qualified')->count();
        $notQualified = $asesmen->where('kode_rekomendasi_agregat', 'not_qualified')->count();

        $peringkat = $asesmen
            ->sortByDesc(fn (Assessment $a): float => $a->job_fit_persen_pratinjau !== null
                ? (float) $a->job_fit_persen_pratinjau
                : -1.0)
            ->values();

        return [
            'total' => $total,
            'draf' => $draf,
            'terintegrasi' => $terintegrasi,
            'final' => $final,
            'dinilai' => $denganJobFit->count(),
            'belum_dinilai' => $total - $denganJobFit->count(),
            'rata_job_fit' => $nilaiJobFit->isNotEmpty() ? round($nilaiJobFit->avg(), 1) : null,
            'job_fit_tertinggi' => $nilaiJobFit->isNotEmpty() ? round($nilaiJobFit->max(), 1) : null,
            'job_fit_terendah' => $nilaiJobFit->isNotEmpty() ? round($nilaiJobFit->min(), 1) : null,
            'qualified' => $qualified,
            'not_qualified' => $notQualified,
            'belum_rekomendasi' => $total - $qualified - $notQualified,
            'peringkat' => $peringkat,
        ];
    }
}
