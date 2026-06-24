<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreApiTokenRequest;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Support\CatatAktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function index(): View
    {
        $tokens = PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->with('tokenable')
            ->orderByDesc('created_at')
            ->get();

        return view('master.api-tokens', [
            'tokens' => $tokens,
            'tokenBaru' => session('api_token_baru'),
        ]);
    }

    public function store(StoreApiTokenRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $baru = $user->createToken($request->validated('nama'), ['read']);

        CatatAktivitas::catat(
            $user,
            'master.token_api.dibuat',
            PersonalAccessToken::class,
            $baru->accessToken->id,
            ['nama' => $request->validated('nama'), 'abilities' => ['read']],
        );

        return redirect()
            ->route('master.token-api.index')
            ->with('api_token_baru', $baru->plainTextToken)
            ->with('status', 'Token API dibuat. Salin sekarang — token tidak ditampilkan lagi.');
    }

    public function destroy(Request $request, PersonalAccessToken $tokenApi): RedirectResponse
    {
        $nama = $tokenApi->name;
        $tokenApi->delete();

        CatatAktivitas::catat(
            $request->user(),
            'master.token_api.dicabut',
            PersonalAccessToken::class,
            $tokenApi->id,
            ['nama' => $nama],
        );

        return redirect()
            ->route('master.token-api.index')
            ->with('status', 'Token API dicabut.');
    }
}
