<?php

namespace App\Http\Middleware;

use App\Support\ConsultantAccessSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKonsultanPenugasanToken
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || $user->role !== 'konsultan') {
            return $next($request);
        }

        if ($request->routeIs('asesmen.token*')) {
            return $next($request);
        }

        if (ConsultantAccessSession::penugasanAktif($user) !== null) {
            return $next($request);
        }

        return redirect()->route('asesmen.token');
    }
}
