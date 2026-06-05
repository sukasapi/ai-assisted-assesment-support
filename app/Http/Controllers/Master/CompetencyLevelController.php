<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\RedirectsToCompetencyStructure;
use App\Http\Requests\Master\StoreCompetencyLevelRequest;
use App\Http\Requests\Master\UpdateCompetencyLevelRequest;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use Illuminate\Http\RedirectResponse;

class CompetencyLevelController extends Controller
{
    use RedirectsToCompetencyStructure;
    public function create(): RedirectResponse
    {
        return redirect()->route('master.kompetensi.index');
    }

    public function store(StoreCompetencyLevelRequest $request): RedirectResponse
    {
        CompetencyLevel::query()->create($request->validated());

        return $this->redirectToCompetencyStructure('Tingkat kompetensi disimpan.');
    }

    public function edit(CompetencyLevel $tingkatKompetensi): RedirectResponse
    {
        return redirect()->route('master.kompetensi.index');
    }

    public function update(UpdateCompetencyLevelRequest $request, CompetencyLevel $tingkatKompetensi): RedirectResponse
    {
        $tingkatKompetensi->update($request->validated());

        return $this->redirectToCompetencyStructure('Tingkat kompetensi diperbarui.');
    }

    public function destroy(CompetencyLevel $tingkatKompetensi): RedirectResponse
    {
        $tingkatKompetensi->delete();

        return $this->redirectToCompetencyStructure('Tingkat kompetensi dihapus.');
    }
}
