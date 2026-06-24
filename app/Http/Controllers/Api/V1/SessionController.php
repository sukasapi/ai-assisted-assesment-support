<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SessionResource;
use App\Models\AssessmentSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SessionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $sesi = AssessmentSession::query()
            ->withCount('assessments')
            ->orderByDesc('dibuat_pada')
            ->paginate(min(100, max(1, (int) $request->integer('per_page', 20))));

        return SessionResource::collection($sesi);
    }

    public function show(AssessmentSession $sesiAsesmen): SessionResource
    {
        $sesiAsesmen->load(['assessments.participant']);

        return new SessionResource($sesiAsesmen, denganPeringkat: true);
    }
}
