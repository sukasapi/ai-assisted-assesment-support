<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentSessionRequest;
use App\Http\Requests\UpdateAssessmentSessionRequest;
use App\Models\AssessmentSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentSessionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AssessmentSession::class);

        $daftar = AssessmentSession::query()
            ->withCount('assessments')
            ->orderByDesc('dibuat_pada')
            ->paginate(15);

        return view('assessment-sessions.index', compact('daftar'));
    }

    public function create(): View
    {
        $this->authorize('create', AssessmentSession::class);

        return view('assessment-sessions.create');
    }

    public function store(StoreAssessmentSessionRequest $request): RedirectResponse
    {
        $sesi = AssessmentSession::query()->create([
            ...$request->validated(),
            'id_pengguna_pembuat' => $request->user()?->id,
        ]);

        return redirect()
            ->route('sesi-asesmen.show', $sesi)
            ->with('status', 'Sesi assessment disimpan.');
    }

    public function show(AssessmentSession $sesiAsesmen): View
    {
        $this->authorize('view', $sesiAsesmen);

        $sesiAsesmen->load([
            'assessments.participant',
            'assessments.matrixVersion',
            'consultantAssignments.consultant',
            'consultantAssignments.assessments.participant',
        ]);

        $konsultanKandidat = \App\Models\User::query()
            ->where('peran', 'konsultan')
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();

        return view('assessment-sessions.show', [
            'sesi' => $sesiAsesmen,
            'konsultanKandidat' => $konsultanKandidat,
        ]);
    }

    public function edit(AssessmentSession $sesiAsesmen): View
    {
        $this->authorize('update', $sesiAsesmen);

        return view('assessment-sessions.edit', ['item' => $sesiAsesmen]);
    }

    public function update(UpdateAssessmentSessionRequest $request, AssessmentSession $sesiAsesmen): RedirectResponse
    {
        $sesiAsesmen->update($request->validated());

        return redirect()
            ->route('sesi-asesmen.show', $sesiAsesmen)
            ->with('status', 'Sesi assessment diperbarui.');
    }

    public function destroy(AssessmentSession $sesiAsesmen): RedirectResponse
    {
        $this->authorize('delete', $sesiAsesmen);

        if ($sesiAsesmen->assessments()->exists()) {
            return redirect()
                ->route('sesi-asesmen.index')
                ->with('error', 'Tidak dapat menghapus: sesi masih memiliki asesmen.');
        }

        $sesiAsesmen->delete();

        return redirect()->route('sesi-asesmen.index')->with('status', 'Sesi dihapus.');
    }
}
