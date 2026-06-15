<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreParticipantRequest;
use App\Http\Requests\Master\UpdateParticipantRequest;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;

class ParticipantMasterController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('master.peserta.index');
    }

    public function store(StoreParticipantRequest $request): RedirectResponse
    {
        Participant::query()->create($request->validated());

        return redirect()->route('master.peserta.index')->with('status', 'Peserta disimpan.');
    }

    public function edit(Participant $peserta): RedirectResponse
    {
        return redirect()->route('master.peserta.index');
    }

    public function update(UpdateParticipantRequest $request, Participant $peserta): RedirectResponse
    {
        $peserta->update($request->validated());

        return redirect()->route('master.peserta.index')->with('status', 'Peserta diperbarui.');
    }

    public function destroy(Participant $peserta): RedirectResponse
    {
        if ($peserta->assessments()->exists()) {
            return redirect()
                ->route('master.peserta.index')
                ->with('error', 'Tidak dapat menghapus: peserta masih memiliki asesmen.');
        }

        $peserta->delete();

        return redirect()->route('master.peserta.index')->with('status', 'Peserta dihapus.');
    }
}
