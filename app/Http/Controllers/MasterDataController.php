<?php

namespace App\Http\Controllers;

use App\Models\AiOpenRouterModel;
use App\Models\User;
use App\Models\AiPromptTemplate;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\CompetencyLevel;
use App\Models\MatrixVersion;
use App\Models\Participant;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('master.index');
    }

    public function competencyGroups(): View
    {
        return $this->competencies();
    }

    public function competencies(): View
    {
        $kelompok = CompetencyGroup::query()
            ->with([
                'competencies' => fn ($q) => $q->orderBy('kode_kompetensi'),
                'competencies.levels' => fn ($q) => $q->orderBy('tingkat'),
            ])
            ->orderBy('kode')
            ->get();

        $jumlahBaris = $kelompok->sum(function (CompetencyGroup $g): int {
            return 1 + $g->competencies->sum(fn (Competency $c): int => 1 + $c->levels->count());
        });

        return view('master.competencies', [
            'kelompok' => $kelompok,
            'jumlahBaris' => $jumlahBaris,
            'bolehUbah' => auth()->user()?->role === 'admin',
        ]);
    }

    public function competencyLevels(): View
    {
        return $this->competencies();
    }

    public function assessmentTools(): View
    {
        $items = AssessmentTool::query()->orderBy('urutan')->orderBy('kode')->paginate(25);

        return view('master.assessment-tools', compact('items'));
    }

    public function matrixVersions(): View
    {
        $items = MatrixVersion::query()->orderByDesc('bawaan')->orderBy('kode_versi')->paginate(25);

        return view('master.matrix-versions', compact('items'));
    }

    public function participants(): View
    {
        $items = Participant::query()
            ->with('matrixVersion')
            ->orderBy('kode_peserta')
            ->paginate(25);

        return view('master.participants', compact('items'));
    }

    public function aiPromptTemplates(): View
    {
        $items = AiPromptTemplate::query()
            ->with('tools:id,kode,nama')
            ->orderBy('urutan')
            ->orderBy('nama')
            ->paginate(25);

        return view('master.ai-prompt-templates', compact('items'));
    }

    public function aiModels(): View
    {
        $items = AiOpenRouterModel::query()
            ->orderBy('urutan')
            ->orderBy('label')
            ->paginate(25);

        return view('master.ai-models', compact('items'));
    }

    public function users(): View
    {
        $items = User::query()
            ->orderBy('peran')
            ->orderBy('nama')
            ->paginate(25);

        return view('master.users', compact('items'));
    }
}
