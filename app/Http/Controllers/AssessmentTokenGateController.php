<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyConsultantTokenRequest;
use App\Models\ConsultantAssignment;
use App\Support\ConsultantAccessSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssessmentTokenGateController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();
        abort_unless((bool) $user?->isKonsultan(), 403);

        if (ConsultantAccessSession::penugasanAktif($user) !== null) {
            return redirect()->route('asesmen.index');
        }

        return view('assessments.token-gate');
    }

    public function verify(VerifyConsultantTokenRequest $request): RedirectResponse
    {
        $user = $request->user();
        $token = $request->string('token_akses')->toString();

        $penugasan = ConsultantAssignment::query()
            ->where('token_akses', $token)
            ->where('id_pengguna', $user->id)
            ->first();

        if ($penugasan === null || ! $penugasan->masihBerlaku()) {
            return back()->withErrors([
                'token_akses' => 'Token tidak valid, kedaluwarsa, atau bukan untuk akun Anda.',
            ]);
        }

        ConsultantAccessSession::set($penugasan->id);

        return redirect()
            ->route('asesmen.index')
            ->with('status', 'Token diterima. Menampilkan asesmen yang ditugaskan.');
    }

    public function clear(): RedirectResponse
    {
        ConsultantAccessSession::clear();

        return redirect()
            ->route('asesmen.token')
            ->with('status', 'Token dihapus dari sesi. Masukkan token baru untuk mengakses asesmen.');
    }
}
