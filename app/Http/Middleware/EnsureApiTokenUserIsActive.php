<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak request API bila pemilik token nonaktif (mis. akun admin dinonaktifkan).
 */
class EnsureApiTokenUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->aktif) {
            return response()->json([
                'message' => 'Akun pemilik token tidak aktif.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
