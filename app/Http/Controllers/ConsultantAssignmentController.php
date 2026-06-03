<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultantAssignmentRequest;
use App\Models\AssessmentSession;
use App\Models\ConsultantAssignment;
use App\Services\Consultant\ConsultantAccessTokenGenerator;
use App\Support\CatatAktivitas;
use Illuminate\Http\RedirectResponse;

class ConsultantAssignmentController extends Controller
{
    public function store(StoreConsultantAssignmentRequest $request, AssessmentSession $sesiAsesmen): RedirectResponse
    {
        $token = ConsultantAccessTokenGenerator::generate();

        $penugasan = ConsultantAssignment::query()->create([
            'id_pengguna' => $request->integer('id_pengguna'),
            'id_sesi_asesmen' => $sesiAsesmen->id,
            'token_akses' => $token,
            'aktif' => true,
            'kedaluwarsa_pada' => $request->filled('kedaluwarsa_pada') ? $request->date('kedaluwarsa_pada') : null,
            'id_pengguna_pembuat' => $request->user()?->id,
        ]);

        $penugasan->assessments()->sync($request->input('id_asesmen', []));

        CatatAktivitas::catat(
            $request->user(),
            'penugasan_konsultan.dibuat',
            ConsultantAssignment::class,
            $penugasan->id,
            ['token' => $token, 'id_sesi' => $sesiAsesmen->id],
        );

        return redirect()
            ->route('sesi-asesmen.show', $sesiAsesmen)
            ->with('status', "Penugasan konsultan dibuat. Token akses: {$token}")
            ->with('token_baru', $token);
    }

    public function regenerate(AssessmentSession $sesiAsesmen, ConsultantAssignment $penugasan): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless($penugasan->id_sesi_asesmen === $sesiAsesmen->id, 404);

        $token = ConsultantAccessTokenGenerator::generate();
        $penugasan->update([
            'token_akses' => $token,
            'aktif' => true,
        ]);

        return redirect()
            ->route('sesi-asesmen.show', $sesiAsesmen)
            ->with('status', "Token baru: {$token}")
            ->with('token_baru', $token);
    }

    public function deactivate(AssessmentSession $sesiAsesmen, ConsultantAssignment $penugasan): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless($penugasan->id_sesi_asesmen === $sesiAsesmen->id, 404);

        $penugasan->update(['aktif' => false]);

        return redirect()
            ->route('sesi-asesmen.show', $sesiAsesmen)
            ->with('status', 'Penugasan konsultan dinonaktifkan.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }
}
