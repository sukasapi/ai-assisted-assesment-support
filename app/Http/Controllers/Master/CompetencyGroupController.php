<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\RedirectsToCompetencyStructure;
use App\Http\Requests\Master\StoreCompetencyGroupRequest;
use App\Http\Requests\Master\UpdateCompetencyGroupRequest;
use App\Models\CompetencyGroup;
use Illuminate\Http\RedirectResponse;

class CompetencyGroupController extends Controller
{
    use RedirectsToCompetencyStructure;
    public function create(): View
    {
        return redirect()->route('master.kompetensi.index');
    }

    public function store(StoreCompetencyGroupRequest $request): RedirectResponse
    {
        CompetencyGroup::query()->create($request->validated());

        return $this->redirectToCompetencyStructure('Kelompok kompetensi disimpan.');
    }

    public function edit(CompetencyGroup $kelompokKompetensi): RedirectResponse
    {
        return redirect()->route('master.kompetensi.index');
    }

    public function update(UpdateCompetencyGroupRequest $request, CompetencyGroup $kelompokKompetensi): RedirectResponse
    {
        $kelompokKompetensi->update($request->validated());

        return $this->redirectToCompetencyStructure('Kelompok kompetensi diperbarui.');
    }

    public function destroy(CompetencyGroup $kelompokKompetensi): RedirectResponse
    {
        if ($kelompokKompetensi->competencies()->exists()) {
            return $this->redirectToCompetencyStructure(error: 'Tidak dapat menghapus: masih ada kompetensi pada kelompok ini.');
        }

        $kelompokKompetensi->delete();

        return $this->redirectToCompetencyStructure('Kelompok kompetensi dihapus.');
    }
}
