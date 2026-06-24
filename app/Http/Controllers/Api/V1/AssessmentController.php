<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\AssessmentResource;
use App\Http\Resources\Api\AssessmentSummaryResource;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssessmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Assessment::query()
            ->with('participant')
            ->when($request->filled('id_sesi_asesmen'), fn ($q) => $q->where('id_sesi_asesmen', $request->integer('id_sesi_asesmen')))
            ->when($request->filled('id_peserta'), fn ($q) => $q->where('id_peserta', $request->integer('id_peserta')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->orderByDesc('id');

        $asesmen = $query->paginate(min(100, max(1, (int) $request->integer('per_page', 20))));

        return AssessmentSummaryResource::collection($asesmen);
    }

    public function show(Assessment $asesmen): AssessmentResource
    {
        $asesmen->load([
            'participant',
            'matrixVersion',
            'assessorAssignments.user',
            'competencyIntegrations.competency.group',
            'keyBehaviors' => fn ($q) => $q->where('tervalidasi', true)
                ->with(['tool', 'competency', 'competencyLevel']),
        ]);

        return new AssessmentResource($asesmen);
    }
}
