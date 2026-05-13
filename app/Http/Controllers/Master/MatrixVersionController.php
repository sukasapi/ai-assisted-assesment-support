<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreMatrixVersionRequest;
use App\Http\Requests\Master\UpdateMatrixVersionRequest;
use App\Models\Assessment;
use App\Models\MatrixVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatrixVersionController extends Controller
{
    public function create(): View
    {
        return view('master.matrix-versions.create');
    }

    public function store(StoreMatrixVersionRequest $request): RedirectResponse
    {
        MatrixVersion::query()->create($request->validated());

        return redirect()->route('master.versi-matriks.index')->with('status', 'Versi matriks disimpan.');
    }

    public function edit(MatrixVersion $versiMatriks): View
    {
        return view('master.matrix-versions.edit', ['item' => $versiMatriks]);
    }

    public function update(UpdateMatrixVersionRequest $request, MatrixVersion $versiMatriks): RedirectResponse
    {
        $versiMatriks->update($request->validated());

        return redirect()->route('master.versi-matriks.index')->with('status', 'Versi matriks diperbarui.');
    }

    public function destroy(MatrixVersion $versiMatriks): RedirectResponse
    {
        if ($versiMatriks->mappings()->exists()) {
            return redirect()->route('master.versi-matriks.index')->with('error', 'Tidak dapat menghapus: masih ada pemetaan kompetensi–alat pada versi ini.');
        }

        if ($versiMatriks->participants()->exists()) {
            return redirect()->route('master.versi-matriks.index')->with('error', 'Tidak dapat menghapus: masih ada peserta yang memakai versi matriks ini.');
        }

        if (Assessment::query()->where('id_versi_matriks', $versiMatriks->id)->exists()) {
            return redirect()->route('master.versi-matriks.index')->with('error', 'Tidak dapat menghapus: masih ada asesmen yang memakai versi matriks ini.');
        }

        $versiMatriks->delete();

        return redirect()->route('master.versi-matriks.index')->with('status', 'Versi matriks dihapus.');
    }
}
