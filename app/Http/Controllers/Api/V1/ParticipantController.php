<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ParticipantResource;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ParticipantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $peserta = Participant::query()
            ->when($request->filled('q'), function ($q) use ($request): void {
                $cari = '%'.$request->string('q')->toString().'%';
                $q->where(fn ($w) => $w->where('nama_lengkap', 'like', $cari)->orWhere('kode_peserta', 'like', $cari));
            })
            ->orderBy('nama_lengkap')
            ->paginate(min(100, max(1, (int) $request->integer('per_page', 20))));

        return ParticipantResource::collection($peserta);
    }

    public function show(Participant $peserta): ParticipantResource
    {
        return new ParticipantResource($peserta);
    }
}
