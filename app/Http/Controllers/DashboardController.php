<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\CompetencyIntegration;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalAsesmen = Assessment::query()->count();
        $draf = Assessment::query()->where('status', AssessmentStatus::Draf)->count();
        $terintegrasi = Assessment::query()->where('status', AssessmentStatus::Terintegrasi)->count();
        $selesaiFinal = Assessment::query()->where('status', AssessmentStatus::SelesaiFinal)->count();

        $rataJobFit = Assessment::query()
            ->whereNotNull('job_fit_persen_pratinjau')
            ->avg('job_fit_persen_pratinjau');

        $asesmenPerluIntegrasi = Assessment::query()
            ->whereIn('status', [AssessmentStatus::Draf->value, AssessmentStatus::Berlangsung->value])
            ->whereNull('integrasi_pratinjau_pada')
            ->count();

        $gapTerbesar = CompetencyIntegration::query()
            ->with(['competency', 'assessment.participant'])
            ->where('selisih_gap', '>', 0)
            ->orderByDesc('selisih_gap')
            ->limit(3)
            ->get();

        return view('dashboard', [
            'totalAsesmen' => $totalAsesmen,
            'draf' => $draf,
            'terintegrasi' => $terintegrasi,
            'selesaiFinal' => $selesaiFinal,
            'rataJobFit' => $rataJobFit !== null ? round((float) $rataJobFit, 1) : null,
            'asesmenPerluIntegrasi' => $asesmenPerluIntegrasi,
            'gapTerbesar' => $gapTerbesar,
        ]);
    }
}
