<?php

use App\Http\Controllers\Api\V1\AssessmentController;
use App\Http\Controllers\Api\V1\CompetencyController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\SessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API publik v1 (read-only)
|--------------------------------------------------------------------------
| Autentikasi: Bearer token Sanctum (kelola di Master data → Token API).
| Token wajib punya ability "read". Rate limit: throttle:api (60/menit).
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'api.aktif'])->group(function () {
    Route::get('me', MeController::class)->name('api.v1.me');

    Route::middleware('ability:read')->group(function () {
        Route::get('sesi-asesmen', [SessionController::class, 'index'])->name('api.v1.sesi.index');
        Route::get('sesi-asesmen/{sesiAsesmen}', [SessionController::class, 'show'])->name('api.v1.sesi.show');

        Route::get('asesmen', [AssessmentController::class, 'index'])->name('api.v1.asesmen.index');
        Route::get('asesmen/{asesmen}', [AssessmentController::class, 'show'])->name('api.v1.asesmen.show');

        Route::get('peserta', [ParticipantController::class, 'index'])->name('api.v1.peserta.index');
        Route::get('peserta/{peserta}', [ParticipantController::class, 'show'])->name('api.v1.peserta.show');

        Route::get('kompetensi', [CompetencyController::class, 'index'])->name('api.v1.kompetensi.index');
    });
});
