<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CompetencyResource;
use App\Models\Competency;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompetencyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $kompetensi = Competency::query()
            ->where('aktif', true)
            ->with('group')
            ->orderBy('kode_kompetensi')
            ->get();

        return CompetencyResource::collection($kompetensi);
    }
}
