<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        return response()->json([
            'data' => [
                'pengguna' => [
                    'id' => $user?->id,
                    'nama' => $user?->nama,
                    'email' => $user?->alamat_surel,
                    'peran' => $user?->peran,
                ],
                'token' => [
                    'nama' => $token?->name,
                    'abilities' => $token?->abilities ?? [],
                    'terakhir_dipakai' => $token?->last_used_at?->toIso8601String(),
                ],
            ],
        ]);
    }
}
