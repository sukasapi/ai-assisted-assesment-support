<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\RedirectsToCompetencyStructure;
use App\Http\Requests\Master\StoreCompetencyRequest;
use App\Http\Requests\Master\UpdateCompetencyRequest;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CompetencyController extends Controller
{
    use RedirectsToCompetencyStructure;
    public function create(): RedirectResponse
    {
        return redirect()->route('master.kompetensi.index');
    }

    public function store(StoreCompetencyRequest $request): RedirectResponse
    {
        Competency::query()->create($request->validated());

        return $this->redirectToCompetencyStructure('Kompetensi disimpan.');
    }

    public function edit(Competency $kompetensi): RedirectResponse
    {
        return redirect()->route('master.kompetensi.index');
    }

    public function update(UpdateCompetencyRequest $request, Competency $kompetensi): RedirectResponse
    {
        $kompetensi->update($request->validated());

        return $this->redirectToCompetencyStructure('Kompetensi diperbarui.');
    }

    public function destroy(Competency $kompetensi): RedirectResponse
    {
        DB::transaction(function () use ($kompetensi): void {
            $kompetensi->levels()->get()->each(fn ($level) => $level->delete());
            $kompetensi->delete();
        });

        return $this->redirectToCompetencyStructure('Kompetensi dan tingkat terkait dihapus.');
    }
}
