<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreParticipantRequest;
use App\Http\Requests\Master\UpdateParticipantRequest;
use App\Models\MatrixVersion;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParticipantMasterController extends Controller
{
    public function create(): View
    {
        return view('master.participants.create', [
            'versiMatriks' => MatrixVersion::query()->orderBy('kode_versi')->get(),
        ]);
    }

    public function store(StoreParticipantRequest $request): RedirectResponse
    {
        Participant::query()->create($request->validated());

        return redirect()->route('master.peserta.index')->with('status', 'Peserta disimpan.');
    }

    public function edit(Participant $peserta): View
    {
        return view('master.participants.edit', [
            'item' => $peserta,
            'versiMatriks' => MatrixVersion::query()->orderBy('kode_versi')->get(),
        ]);
    }

    public function update(UpdateParticipantRequest $request, Participant $peserta): RedirectResponse
    {
        $peserta->update($request->validated());

        return redirect()->route('master.peserta.index')->with('status', 'Peserta diperbarui.');
    }

    public function destroy(Participant $peserta): RedirectResponse
    {
        $peserta->delete();

        return redirect()->route('master.peserta.index')->with('status', 'Peserta dihapus.');
    }
}
