<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Master\AiPromptTemplateController;
use App\Models\AiOpenRouterModel;
use App\Models\AiPromptTemplate;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use App\Support\CompetencyTreeFilter;
use App\Support\TableSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('master.index');
    }

    public function competencyGroups(Request $request): View
    {
        return $this->competencies($request);
    }

    public function competencies(Request $request): View
    {
        $kelompok = CompetencyGroup::query()
            ->with([
                'competencies' => fn ($q) => $q->orderBy('kode_kompetensi'),
                'competencies.levels' => fn ($q) => $q->orderBy('tingkat'),
            ])
            ->orderBy('kode')
            ->get();

        $kelompok = CompetencyTreeFilter::filterGroups($kelompok, $request->query('q'));

        $jumlahBaris = $kelompok->sum(function (CompetencyGroup $g): int {
            return 1 + $g->competencies->sum(fn (Competency $c): int => 1 + $c->levels->count());
        });

        return view('master.competencies', [
            'kelompok' => $kelompok,
            'jumlahBaris' => $jumlahBaris,
            'bolehUbah' => (bool) auth()->user()?->isAdmin(),
        ]);
    }

    public function competencyLevels(Request $request): View
    {
        return $this->competencies($request);
    }

    public function assessmentTools(Request $request): View
    {
        $query = AssessmentTool::query()->orderBy('urutan')->orderBy('kode');
        TableSearch::apply($query, $request->query('q'), ['kode', 'nama', 'deskripsi']);

        $items = $query->paginate(25)->withQueryString();

        return view('master.assessment-tools', compact('items'));
    }

    public function matrixVersions(Request $request): View
    {
        $query = MatrixVersion::query()->orderByDesc('bawaan')->orderBy('kode_versi');
        TableSearch::apply($query, $request->query('q'), ['kode_versi', 'nama_versi']);

        $items = $query->paginate(25)->withQueryString();

        return view('master.matrix-versions', compact('items'));
    }

    public function participants(Request $request): View
    {
        $query = Participant::query()
            ->with('matrixVersion')
            ->orderBy('kode_peserta');

        TableSearch::apply($query, $request->query('q'), [
            'kode_peserta',
            'nama_lengkap',
            'alamat_surel',
            fn ($q, $term) => $q->orWhereHas('matrixVersion', fn ($m) => $m->where('kode_versi', 'like', '%'.$term.'%')),
        ]);

        $items = $query->paginate(25)->withQueryString();
        $versiMatriks = MatrixVersion::query()->orderBy('kode_versi')->get();

        return view('master.participants', compact('items', 'versiMatriks'));
    }

    public function aiPromptTemplates(Request $request): View
    {
        $query = AiPromptTemplate::query()
            ->with('tools:id,kode,nama')
            ->orderBy('urutan')
            ->orderBy('nama');

        TableSearch::apply($query, $request->query('q'), ['kode', 'nama', 'deskripsi']);

        $items = $query->paginate(25)->withQueryString();
        $alatPenilaian = AiPromptTemplateController::daftarAlatAktif();

        return view('master.ai-prompt-templates', compact('items', 'alatPenilaian'));
    }

    public function aiModels(Request $request): View
    {
        $query = AiOpenRouterModel::query()
            ->orderBy('urutan')
            ->orderBy('label');

        TableSearch::apply($query, $request->query('q'), ['id_model_openrouter', 'label']);

        $items = $query->paginate(25)->withQueryString();

        return view('master.ai-models', compact('items'));
    }

    public function users(Request $request): View
    {
        $query = User::query()
            ->orderBy('peran')
            ->orderBy('nama');

        TableSearch::apply($query, $request->query('q'), ['nama', 'alamat_surel', 'peran']);

        $items = $query->paginate(25)->withQueryString();

        return view('master.users', compact('items'));
    }
}
