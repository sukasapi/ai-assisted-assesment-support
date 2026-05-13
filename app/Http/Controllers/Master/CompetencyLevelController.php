<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCompetencyLevelRequest;
use App\Http\Requests\Master\UpdateCompetencyLevelRequest;
use App\Models\Competency;
use App\Models\CompetencyLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompetencyLevelController extends Controller
{
    public function create(): View
    {
        return view('master.competency-levels.create', [
            'kompetensi' => Competency::query()->orderBy('kode_kompetensi')->get(),
        ]);
    }

    public function store(StoreCompetencyLevelRequest $request): RedirectResponse
    {
        CompetencyLevel::query()->create($request->validated());

        return redirect()->route('master.tingkat-kompetensi.index')->with('status', 'Tingkat kompetensi disimpan.');
    }

    public function edit(CompetencyLevel $tingkatKompetensi): View
    {
        return view('master.competency-levels.edit', [
            'item' => $tingkatKompetensi,
            'kompetensi' => Competency::query()->orderBy('kode_kompetensi')->get(),
        ]);
    }

    public function update(UpdateCompetencyLevelRequest $request, CompetencyLevel $tingkatKompetensi): RedirectResponse
    {
        $tingkatKompetensi->update($request->validated());

        return redirect()->route('master.tingkat-kompetensi.index')->with('status', 'Tingkat kompetensi diperbarui.');
    }

    public function destroy(CompetencyLevel $tingkatKompetensi): RedirectResponse
    {
        $tingkatKompetensi->delete();

        return redirect()->route('master.tingkat-kompetensi.index')->with('status', 'Tingkat kompetensi dihapus.');
    }
}
