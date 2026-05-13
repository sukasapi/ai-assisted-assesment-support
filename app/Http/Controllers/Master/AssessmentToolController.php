<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAssessmentToolRequest;
use App\Http\Requests\Master\UpdateAssessmentToolRequest;
use App\Models\AssessmentTool;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentToolController extends Controller
{
    public function create(): View
    {
        return view('master.assessment-tools.create');
    }

    public function store(StoreAssessmentToolRequest $request): RedirectResponse
    {
        AssessmentTool::query()->create($request->validated());

        return redirect()->route('master.alat-penilaian.index')->with('status', 'Alat penilaian disimpan.');
    }

    public function edit(AssessmentTool $alatPenilaian): View
    {
        return view('master.assessment-tools.edit', ['item' => $alatPenilaian]);
    }

    public function update(UpdateAssessmentToolRequest $request, AssessmentTool $alatPenilaian): RedirectResponse
    {
        $alatPenilaian->update($request->validated());

        return redirect()->route('master.alat-penilaian.index')->with('status', 'Alat penilaian diperbarui.');
    }

    public function destroy(AssessmentTool $alatPenilaian): RedirectResponse
    {
        if ($alatPenilaian->mappings()->exists()) {
            return redirect()->route('master.alat-penilaian.index')->with('error', 'Tidak dapat menghapus: alat masih dipakai pada pemetaan kompetensi–alat.');
        }

        $alatPenilaian->delete();

        return redirect()->route('master.alat-penilaian.index')->with('status', 'Alat penilaian dihapus.');
    }
}
