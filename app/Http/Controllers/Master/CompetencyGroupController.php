<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCompetencyGroupRequest;
use App\Http\Requests\Master\UpdateCompetencyGroupRequest;
use App\Models\CompetencyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompetencyGroupController extends Controller
{
    public function create(): View
    {
        return view('master.competency-groups.create');
    }

    public function store(StoreCompetencyGroupRequest $request): RedirectResponse
    {
        CompetencyGroup::query()->create($request->validated());

        return redirect()->route('master.kelompok-kompetensi.index')->with('status', 'Kelompok kompetensi disimpan.');
    }

    public function edit(CompetencyGroup $kelompokKompetensi): View
    {
        return view('master.competency-groups.edit', ['item' => $kelompokKompetensi]);
    }

    public function update(UpdateCompetencyGroupRequest $request, CompetencyGroup $kelompokKompetensi): RedirectResponse
    {
        $kelompokKompetensi->update($request->validated());

        return redirect()->route('master.kelompok-kompetensi.index')->with('status', 'Kelompok kompetensi diperbarui.');
    }

    public function destroy(CompetencyGroup $kelompokKompetensi): RedirectResponse
    {
        if ($kelompokKompetensi->competencies()->exists()) {
            return redirect()->route('master.kelompok-kompetensi.index')->with('error', 'Tidak dapat menghapus: masih ada kompetensi pada kelompok ini.');
        }

        $kelompokKompetensi->delete();

        return redirect()->route('master.kelompok-kompetensi.index')->with('status', 'Kelompok kompetensi dihapus.');
    }
}
