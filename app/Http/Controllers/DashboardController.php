<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentSession;
use App\Models\CompetencyIntegration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if ($user?->isKonsultan()) {
            return view('dashboard', array_merge(['peran' => 'konsultan'], $this->dataKonsultan($user)));
        }

        return view('dashboard', array_merge(['peran' => 'admin'], $this->dataAdmin()));
    }

    /**
     * @return array<string, mixed>
     */
    private function dataAdmin(): array
    {
        $rataJobFit = Assessment::query()
            ->whereNotNull('job_fit_persen_pratinjau')
            ->avg('job_fit_persen_pratinjau');

        return [
            'totalAsesmen' => Assessment::query()->count(),
            'draf' => Assessment::query()->where('status', AssessmentStatus::Draf)->count(),
            'terintegrasi' => Assessment::query()->where('status', AssessmentStatus::Terintegrasi)->count(),
            'selesaiFinal' => Assessment::query()->where('status', AssessmentStatus::SelesaiFinal)->count(),
            'rataJobFit' => $rataJobFit !== null ? round((float) $rataJobFit, 1) : null,
            'asesmenPerluIntegrasi' => Assessment::query()
                ->whereIn('status', [AssessmentStatus::Draf->value, AssessmentStatus::Berlangsung->value])
                ->whereNull('integrasi_pratinjau_pada')
                ->count(),
            'totalSesi' => AssessmentSession::query()->count(),
            'siapFinalisasi' => Assessment::query()
                ->where('status', AssessmentStatus::Terintegrasi)
                ->with(['participant', 'session'])
                ->orderByDesc('integrasi_pratinjau_pada')
                ->limit(6)
                ->get(),
            'gapTerbesar' => CompetencyIntegration::query()
                ->with(['competency', 'assessment.participant'])
                ->where('selisih_gap', '>', 0)
                ->orderByDesc('selisih_gap')
                ->limit(5)
                ->get(),
            'sesiTerbaru' => AssessmentSession::query()
                ->withCount('assessments')
                ->orderByDesc('dibuat_pada')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dataKonsultan(User $user): array
    {
        $penugasan = $user->consultantAssignments()
            ->with(['session', 'assessments.participant', 'assessments.session'])
            ->get()
            ->filter(fn ($p) => $p->masihBerlaku());

        /** @var Collection<int, Assessment> $asesmen */
        $asesmen = $penugasan
            ->flatMap(fn ($p) => $p->assessments)
            ->unique('id')
            ->values();

        $perluTindakan = $asesmen
            ->filter(fn (Assessment $a): bool => $a->status !== AssessmentStatus::SelesaiFinal)
            ->sortBy(fn (Assessment $a): string => $a->participant?->nama_lengkap ?? '')
            ->take(10)
            ->values();

        return [
            'jumlahPenugasan' => $penugasan->count(),
            'totalAsesmen' => $asesmen->count(),
            'draf' => $asesmen->where('status', AssessmentStatus::Draf)->count(),
            'terintegrasi' => $asesmen->where('status', AssessmentStatus::Terintegrasi)->count(),
            'selesaiFinal' => $asesmen->where('status', AssessmentStatus::SelesaiFinal)->count(),
            'belumDinilai' => $asesmen->filter(fn (Assessment $a): bool => $a->integrasi_pratinjau_pada === null)->count(),
            'perluTindakan' => $perluTindakan,
            'penugasan' => $penugasan->values(),
        ];
    }
}
