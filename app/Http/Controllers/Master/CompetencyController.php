<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreCompetencyRequest;
use App\Http\Requests\Master\UpdateCompetencyRequest;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompetencyController extends Controller
{
    public function create(): View
    {
        return view('master.competencies.create', [
            'kelompok' => CompetencyGroup::query()->orderBy('kode')->get(),
        ]);
    }

    public function store(StoreCompetencyRequest $request): RedirectResponse
    {
        Competency::query()->create($request->validated());

        return redirect()->route('master.kompetensi.index')->with('status', 'Kompetensi disimpan.');
    }

    public function edit(Competency $kompetensi): View
    {
        return view('master.competencies.edit', [
            'item' => $kompetensi,
            'kelompok' => CompetencyGroup::query()->orderBy('kode')->get(),
        ]);
    }

    public function update(UpdateCompetencyRequest $request, Competency $kompetensi): RedirectResponse
    {
        $kompetensi->update($request->validated());

        return redirect()->route('master.kompetensi.index')->with('status', 'Kompetensi diperbarui.');
    }

    public function destroy(Competency $kompetensi): RedirectResponse
    {
        DB::transaction(function () use ($kompetensi): void {
            $kompetensi->levels()->get()->each(fn ($level) => $level->delete());
            $kompetensi->delete();
        });

        return redirect()->route('master.kompetensi.index')->with('status', 'Kompetensi dan tingkat terkait dihapus.');
    }
}
