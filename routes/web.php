<?php

use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\ActivityLogController;
use App\Http\Controllers\Master\AiLogController;
use App\Http\Controllers\Master\AssessmentToolController;
use App\Http\Controllers\Master\CompetencyController;
use App\Http\Controllers\Master\CompetencyGroupController;
use App\Http\Controllers\Master\CompetencyLevelController;
use App\Http\Controllers\Master\CompetencyToolMappingController;
use App\Http\Controllers\Master\MatrixVersionController;
use App\Http\Controllers\Master\ParticipantMasterController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ParticipantImportController;
use App\Models\CompetencyToolMapping;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:admin,konsultan')->group(function () {
        Route::get('master', [MasterDataController::class, 'index'])->name('master.index');
        Route::get('master/kelompok-kompetensi', [MasterDataController::class, 'competencyGroups'])->name('master.kelompok-kompetensi.index');
        Route::get('master/kompetensi', [MasterDataController::class, 'competencies'])->name('master.kompetensi.index');
        Route::get('master/tingkat-kompetensi', [MasterDataController::class, 'competencyLevels'])->name('master.tingkat-kompetensi.index');
        Route::get('master/alat-penilaian', [MasterDataController::class, 'assessmentTools'])->name('master.alat-penilaian.index');
        Route::get('master/versi-matriks', [MasterDataController::class, 'matrixVersions'])->name('master.versi-matriks.index');
        Route::get('master/versi-matriks/{versiMatriks}/pemetaan', [CompetencyToolMappingController::class, 'index'])->name('master.versi-matriks.pemetaan.index');
        Route::get('master/pemetaan-kompetensi-alat', function () {
            return redirect()
                ->route('master.versi-matriks.index')
                ->with('status', 'Pemetaan kompetensi–alat ada di tiap versi matriks: buka Versi matriks, lalu tautan «Pemetaan».');
        })->name('master.pemetaan-kompetensi-alat.index');
        Route::get('master/peserta', [MasterDataController::class, 'participants'])->name('master.peserta.index');

        Route::get('asesmen', [AssessmentController::class, 'index'])->name('asesmen.index');
        Route::get('asesmen/buat', [AssessmentController::class, 'create'])->name('asesmen.create');
        Route::post('asesmen', [AssessmentController::class, 'store'])->name('asesmen.store');
        Route::get('asesmen/{asesmen}/diagnostik-alat', [AssessmentController::class, 'toolDiagnostic'])->name('asesmen.diagnostik-alat');
        Route::patch('asesmen/{asesmen}/metode-koleksi-bukti', [AssessmentController::class, 'updateEvidenceCollectionMode'])->name('asesmen.metode-koleksi-bukti.update');
        Route::post('asesmen/{asesmen}/bukti', [AssessmentController::class, 'storeEvidence'])->name('asesmen.bukti.store');
        Route::post('asesmen/{asesmen}/bukti/{bukti}/analisis-ai', [AssessmentController::class, 'analyzeEvidenceAi'])
            ->middleware('throttle:ai-analysis-trigger')
            ->scopeBindings()
            ->name('asesmen.bukti.analisis-ai');
        Route::post('asesmen/{asesmen}/payload-alat', [AssessmentController::class, 'storeToolPayload'])->name('asesmen.payload-alat.store');
        Route::delete('asesmen/{asesmen}/payload-alat/{payload}', [AssessmentController::class, 'destroyToolPayload'])
            ->scopeBindings()
            ->name('asesmen.payload-alat.destroy');
        Route::get('asesmen/{asesmen}/payload-alat/status', [AssessmentController::class, 'toolPayloadStatuses'])->name('asesmen.payload-alat.statuses');
        Route::get('asesmen/{asesmen}/payload-alat/{payload}', [AssessmentController::class, 'showToolPayload'])->name('asesmen.payload-alat.show');
        Route::get('asesmen/{asesmen}/payload-alat/{payload}/analisis-ai', [AssessmentController::class, 'redirectToolPayloadAiGet'])
            ->scopeBindings()
            ->name('asesmen.payload-alat.analisis-ai.get');
        Route::post('asesmen/{asesmen}/payload-alat/{payload}/analisis-ai', [AssessmentController::class, 'analyzeToolPayloadAi'])
            ->middleware('throttle:ai-analysis-trigger')
            ->scopeBindings()
            ->name('asesmen.payload-alat.analisis-ai');
        Route::post('asesmen/{asesmen}/integrasi/hitung', [AssessmentController::class, 'hitungIntegrasiPratinjau'])->name('asesmen.integrasi.hitung');
        Route::patch('asesmen/{asesmen}/finalisasi', [AssessmentController::class, 'finalize'])->name('asesmen.finalisasi');
        Route::patch('asesmen/{asesmen}/batal-finalisasi', [AssessmentController::class, 'unfinalize'])->name('asesmen.batal-finalisasi');
        Route::post('asesmen/{asesmen}/perilaku-kunci', [AssessmentController::class, 'storeKeyBehavior'])->name('asesmen.perilaku.store');
        Route::get('asesmen/{asesmen}/perilaku-kunci/{perilaku}/ubah', [AssessmentController::class, 'editKeyBehavior'])
            ->scopeBindings()
            ->name('asesmen.perilaku.edit');
        Route::patch('asesmen/{asesmen}/perilaku-kunci/{perilaku}/sahkan', [AssessmentController::class, 'sahkanKeyBehavior'])
            ->scopeBindings()
            ->name('asesmen.perilaku.sahkan');
        Route::patch('asesmen/{asesmen}/perilaku-kunci/{perilaku}', [AssessmentController::class, 'updateKeyBehavior'])
            ->scopeBindings()
            ->name('asesmen.perilaku.update');
        Route::get('asesmen/{asesmen}', [AssessmentController::class, 'show'])->name('asesmen.show');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('master/log-aktivitas', [ActivityLogController::class, 'index'])->name('master.log-aktivitas.index');
        Route::get('master/log-ai', [AiLogController::class, 'index'])->name('master.log-ai.index');

        Route::get('peserta/impor-csv', [ParticipantImportController::class, 'create'])->name('peserta.impor-csv');
        Route::get('peserta/impor-csv/template', [ParticipantImportController::class, 'template'])->name('peserta.impor-csv.template');
        Route::post('peserta/impor-csv', [ParticipantImportController::class, 'store'])->name('peserta.impor-csv.store');

        Route::get('master/kelompok-kompetensi/buat', [CompetencyGroupController::class, 'create'])->name('master.kelompok-kompetensi.create');
        Route::post('master/kelompok-kompetensi', [CompetencyGroupController::class, 'store'])->name('master.kelompok-kompetensi.store');
        Route::get('master/kelompok-kompetensi/{kelompokKompetensi}/ubah', [CompetencyGroupController::class, 'edit'])->name('master.kelompok-kompetensi.edit');
        Route::put('master/kelompok-kompetensi/{kelompokKompetensi}', [CompetencyGroupController::class, 'update'])->name('master.kelompok-kompetensi.update');
        Route::delete('master/kelompok-kompetensi/{kelompokKompetensi}', [CompetencyGroupController::class, 'destroy'])->name('master.kelompok-kompetensi.destroy');

        Route::get('master/kompetensi/buat', [CompetencyController::class, 'create'])->name('master.kompetensi.create');
        Route::post('master/kompetensi', [CompetencyController::class, 'store'])->name('master.kompetensi.store');
        Route::get('master/kompetensi/{kompetensi}/ubah', [CompetencyController::class, 'edit'])->name('master.kompetensi.edit');
        Route::put('master/kompetensi/{kompetensi}', [CompetencyController::class, 'update'])->name('master.kompetensi.update');
        Route::delete('master/kompetensi/{kompetensi}', [CompetencyController::class, 'destroy'])->name('master.kompetensi.destroy');

        Route::get('master/tingkat-kompetensi/buat', [CompetencyLevelController::class, 'create'])->name('master.tingkat-kompetensi.create');
        Route::post('master/tingkat-kompetensi', [CompetencyLevelController::class, 'store'])->name('master.tingkat-kompetensi.store');
        Route::get('master/tingkat-kompetensi/{tingkatKompetensi}/ubah', [CompetencyLevelController::class, 'edit'])->name('master.tingkat-kompetensi.edit');
        Route::put('master/tingkat-kompetensi/{tingkatKompetensi}', [CompetencyLevelController::class, 'update'])->name('master.tingkat-kompetensi.update');
        Route::delete('master/tingkat-kompetensi/{tingkatKompetensi}', [CompetencyLevelController::class, 'destroy'])->name('master.tingkat-kompetensi.destroy');

        Route::get('master/alat-penilaian/buat', [AssessmentToolController::class, 'create'])->name('master.alat-penilaian.create');
        Route::post('master/alat-penilaian', [AssessmentToolController::class, 'store'])->name('master.alat-penilaian.store');
        Route::get('master/alat-penilaian/{alatPenilaian}/ubah', [AssessmentToolController::class, 'edit'])->name('master.alat-penilaian.edit');
        Route::put('master/alat-penilaian/{alatPenilaian}', [AssessmentToolController::class, 'update'])->name('master.alat-penilaian.update');
        Route::delete('master/alat-penilaian/{alatPenilaian}', [AssessmentToolController::class, 'destroy'])->name('master.alat-penilaian.destroy');

        Route::get('master/versi-matriks/buat', [MatrixVersionController::class, 'create'])->name('master.versi-matriks.create');
        Route::post('master/versi-matriks', [MatrixVersionController::class, 'store'])->name('master.versi-matriks.store');
        Route::get('master/versi-matriks/{versiMatriks}/ubah', [MatrixVersionController::class, 'edit'])->name('master.versi-matriks.edit');
        Route::put('master/versi-matriks/{versiMatriks}', [MatrixVersionController::class, 'update'])->name('master.versi-matriks.update');
        Route::delete('master/versi-matriks/{versiMatriks}', [MatrixVersionController::class, 'destroy'])->name('master.versi-matriks.destroy');

        Route::post('master/versi-matriks/{versiMatriks}/pemetaan/sinkron', [CompetencyToolMappingController::class, 'sync'])->name('master.versi-matriks.pemetaan.sync');

        Route::get('master/pemetaan-kompetensi-alat/buat', function () {
            return redirect()
                ->route('master.versi-matriks.index')
                ->with('status', 'Atur pemetaan lewat Versi matriks → Pemetaan: kisi centang kompetensi × alat.');
        });
        Route::get('master/pemetaan-kompetensi-alat/{pemetaan}/ubah', function (CompetencyToolMapping $pemetaan) {
            return redirect()->route('master.versi-matriks.pemetaan.index', [
                'versiMatriks' => $pemetaan->id_versi_matriks,
            ])->with('status', 'Pengaturan pemetaan memakai kisi centang per versi matriks.');
        });

        Route::get('master/peserta/buat', [ParticipantMasterController::class, 'create'])->name('master.peserta.create');
        Route::post('master/peserta', [ParticipantMasterController::class, 'store'])->name('master.peserta.store');
        Route::get('master/peserta/{peserta}/ubah', [ParticipantMasterController::class, 'edit'])->name('master.peserta.edit');
        Route::put('master/peserta/{peserta}', [ParticipantMasterController::class, 'update'])->name('master.peserta.update');
        Route::delete('master/peserta/{peserta}', [ParticipantMasterController::class, 'destroy'])->name('master.peserta.destroy');
    });
});
