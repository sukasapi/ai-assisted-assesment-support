<?php

namespace App\Http\Controllers;

use App\Models\AiOpenRouterModel;
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
        $items = CompetencyGroup::query()->orderBy('kode')->paginate(25);

        return view('master.competency-groups', compact('items'));
    }

    public function competencies(): View
    {
        $items = Competency::query()
            ->with('group')
            ->orderBy('kode_kompetensi')
            ->paginate(25);

        return view('master.competencies', compact('items'));
    }

    public function competencyLevels(): View
    {
        $items = CompetencyLevel::query()
            ->whereHas('competency')
            ->with('competency')
            ->orderBy('id_kompetensi')
            ->orderBy('tingkat')
            ->paginate(40);

        return view('master.competency-levels', compact('items'));
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
}
