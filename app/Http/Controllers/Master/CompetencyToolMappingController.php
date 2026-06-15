<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SyncCompetencyToolGridRequest;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Support\CatatAktivitas;
use App\Support\CompetencyTreeFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompetencyToolMappingController extends Controller
{
    public function index(Request $request, MatrixVersion $versiMatriks): View
    {
        $urutanKelompok = ['INT' => 0, 'MNJ' => 1, 'LDR' => 2];
        $kelompokKompetensi = CompetencyGroup::query()
            ->whereNull('dihapus_pada')
            ->with(['competencies' => function ($query) {
                $query->whereNull('dihapus_pada')->where('aktif', true)->orderBy('kode_kompetensi');
            }])
            ->get()
            ->sortBy(fn (CompetencyGroup $g) => $urutanKelompok[$g->kode] ?? 99)
            ->values()
            ->filter(fn (CompetencyGroup $g) => $g->competencies->isNotEmpty());

        $kelompokKompetensi = CompetencyTreeFilter::filterGroups($kelompokKompetensi, $request->query('q'), includeLevels: false);

        $alat = self::alatAktifUntukGrid();
        $mapped = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $versiMatriks->id)
            ->get()
            ->keyBy(fn (CompetencyToolMapping $m) => $m->id_kompetensi.'-'.$m->id_alat_penilaian);

        $punyaKompetensi = Competency::query()->whereNull('dihapus_pada')->exists();

        return view('master.matrix-version-mappings', compact(
            'versiMatriks',
            'kelompokKompetensi',
            'alat',
            'mapped',
            'punyaKompetensi',
        ));
    }

    public function sync(MatrixVersion $versiMatriks, SyncCompetencyToolGridRequest $request): RedirectResponse
    {
        $desired = $request->desiredPairKeys();

        $idsKompetensi = Competency::query()
            ->whereNull('dihapus_pada')
            ->where('aktif', true)
            ->orderBy('kode_kompetensi')
            ->pluck('id')
            ->all();
        $idsAlat = self::alatAktifUntukGrid()->pluck('id')->all();

        $existing = CompetencyToolMapping::query()
            ->withTrashed()
            ->where('id_versi_matriks', $versiMatriks->id)
            ->get()
            ->keyBy(fn (CompetencyToolMapping $m) => $m->id_kompetensi.'-'.$m->id_alat_penilaian);

        DB::transaction(function () use ($versiMatriks, $desired, $idsKompetensi, $idsAlat, $existing): void {
            foreach ($idsKompetensi as $cId) {
                foreach ($idsAlat as $tId) {
                    $key = $cId.'-'.$tId;
                    $want = isset($desired[$key]);
                    /** @var CompetencyToolMapping|null $row */
                    $row = $existing->get($key);

                    if ($want) {
                        if ($row === null) {
                            CompetencyToolMapping::query()->create([
                                'id_versi_matriks' => $versiMatriks->id,
                                'id_kompetensi' => $cId,
                                'id_alat_penilaian' => $tId,
                                'wajib' => false,
                                'bobot' => 1,
                                'aktif' => true,
                            ]);
                        } elseif ($row->trashed()) {
                            $row->restore();
                        }
                    } elseif ($row !== null && ! $row->trashed()) {
                        $row->delete();
                    }
                }
            }
        });

        CatatAktivitas::catat(
            $request->user(),
            'master.pemetaan_matriks.disinkronkan',
            MatrixVersion::class,
            $versiMatriks->id,
            ['kode_versi' => $versiMatriks->kode_versi, 'jumlah_terpilih' => count($desired)],
        );

        return redirect()->route('master.versi-matriks.pemetaan.index', $versiMatriks)->with('status', 'Pemetaan disimpan.');
    }

    /**
     * Hanya alat yang tampil di kisi — sinkron memakai himpunan yang sama agar pemetaan alat non-aktif / di luar grid tidak terhapus tanpa sengaja.
     *
     * @return Collection<int, AssessmentTool>
     */
    private static function alatAktifUntukGrid(): Collection
    {
        return AssessmentTool::query()
            ->whereNull('dihapus_pada')
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('kode')
            ->get();
    }
}
