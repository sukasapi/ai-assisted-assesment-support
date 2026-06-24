<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssessmentSessionRequest;
use App\Http\Requests\UpdateAssessmentSessionRequest;
use App\Models\AssessmentSession;
use App\Models\User;
use App\Support\SessionAggregate;
use App\Support\TableSearch;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AssessmentSessionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AssessmentSession::class);

        $query = AssessmentSession::query()
            ->withCount('assessments')
            ->orderByDesc('dibuat_pada');

        TableSearch::apply($query, request('q'), ['kode_sesi', 'nama']);

        $daftar = $query->paginate(15)->withQueryString();

        return view('assessment-sessions.index', compact('daftar'));
    }

    public function create(): RedirectResponse
    {
        $this->authorize('create', AssessmentSession::class);

        return redirect()->route('sesi-asesmen.index');
    }

    public function store(StoreAssessmentSessionRequest $request): RedirectResponse
    {
        AssessmentSession::query()->create([
            ...$request->validated(),
            'id_pengguna_pembuat' => $request->user()?->id,
        ]);

        return redirect()
            ->route('sesi-asesmen.index')
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

        $konsultanKandidat = User::query()
            ->where('peran', 'konsultan')
            ->where('aktif', true)
            ->orderBy('nama')
            ->get();

        return view('assessment-sessions.show', [
            'sesi' => $sesiAsesmen,
            'konsultanKandidat' => $konsultanKandidat,
            'ringkasanSesi' => SessionAggregate::untukSesi($sesiAsesmen),
            'bukaModalAsesmenSesi' => session('buka_modal_asesmen_sesi'),
        ]);
    }

    /**
     * Laporan agregat satu sesi (PDF). Tambahkan ?format=html untuk pratinjau.
     */
    public function aggregateReportPdf(Request $request, AssessmentSession $sesiAsesmen): Response
    {
        $this->authorize('view', $sesiAsesmen);

        $sesiAsesmen->load([
            'assessments.participant',
            'assessments.matrixVersion',
            'assessments.assessorAssignments.user',
        ]);

        $data = [
            'sesi' => $sesiAsesmen,
            'ringkasan' => SessionAggregate::untukSesi($sesiAsesmen),
            'dicetakOleh' => $request->user()?->nama ?? '—',
            'dicetakPada' => now(),
        ];

        if ($request->query('format') === 'html') {
            return response()->view('assessment-sessions.report', $data);
        }

        $namaFile = 'laporan-sesi-'.$sesiAsesmen->kode_sesi.'.pdf';

        return Pdf::loadView('assessment-sessions.report', $data)
            ->setPaper('a4', 'portrait')
            ->download($namaFile);
    }

    public function edit(AssessmentSession $sesiAsesmen): RedirectResponse
    {
        $this->authorize('update', $sesiAsesmen);

        return redirect()->route('sesi-asesmen.show', $sesiAsesmen);
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
