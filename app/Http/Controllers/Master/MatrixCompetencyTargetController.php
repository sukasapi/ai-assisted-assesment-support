<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SyncMatrixCompetencyTargetRequest;
use App\Models\Competency;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixCompetencyTarget;
use App\Models\MatrixVersion;
use App\Support\CatatAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatrixCompetencyTargetController extends Controller
{
    public function index(MatrixVersion $versiMatriks): View
    {
        $idKompetensi = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $versiMatriks->id)
            ->pluck('id_kompetensi')
            ->unique()
            ->values();

        $kompetensi = Competency::query()
            ->whereIn('id', $idKompetensi->all())
            ->whereNull('dihapus_pada')
            ->with('group')
            ->orderBy('kode_kompetensi')
            ->get();

        $target = MatrixCompetencyTarget::query()
            ->where('id_versi_matriks', $versiMatriks->id)
            ->pluck('tingkat_target', 'id_kompetensi');

        return view('master.matrix-competency-targets', compact('versiMatriks', 'kompetensi', 'target'));
    }

    public function store(MatrixVersion $versiMatriks, SyncMatrixCompetencyTargetRequest $request): RedirectResponse
    {
        $targets = $request->validated('target', []);

        foreach ($targets as $idKompetensi => $tingkat) {
            $idKompetensi = (int) $idKompetensi;
            $tingkat = is_numeric($tingkat) ? (int) $tingkat : 0;

            if ($tingkat <= 0) {
                MatrixCompetencyTarget::query()
                    ->where('id_versi_matriks', $versiMatriks->id)
                    ->where('id_kompetensi', $idKompetensi)
                    ->delete();

                continue;
            }

            MatrixCompetencyTarget::query()->updateOrCreate(
                ['id_versi_matriks' => $versiMatriks->id, 'id_kompetensi' => $idKompetensi],
                ['tingkat_target' => $tingkat],
            );
        }

        CatatAktivitas::catat(
            $request->user(),
            'master.target_kompetensi_matriks.disimpan',
            MatrixVersion::class,
            $versiMatriks->id,
            ['kode_versi' => $versiMatriks->kode_versi],
        );

        return redirect()
            ->route('master.versi-matriks.target-kompetensi.index', $versiMatriks)
            ->with('status', 'Target kompetensi disimpan. Asesmen BARU akan memakai target ini; asesmen lama tetap memakai snapshot saat dibuat.');
    }
}
