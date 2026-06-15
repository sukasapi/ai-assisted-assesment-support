<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreMatrixRecommendationConfigRequest;
use App\Models\CompetencyGroup;
use App\Models\MatrixVersion;
use App\Services\Recommendation\RecommendationConfigService;
use App\Support\CatatAktivitas;
use App\Support\DefaultRecommendationConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MatrixRecommendationConfigController extends Controller
{
    public function index(
        MatrixVersion $versiMatriks,
        RecommendationConfigService $configService,
    ): View {
        $revisiTerbaru = $configService->revisiTerbaru($versiMatriks);
        $riwayat = $versiMatriks->recommendationConfigRevisions()
            ->with('createdBy')
            ->orderByDesc('nomor_revisi')
            ->limit(20)
            ->get();

        $jumlahAsesmenTerdampak = $configService->hitungJumlahAsesmenTerdampak($versiMatriks->id);
        $kelompokKompetensi = CompetencyGroup::query()
            ->whereNull('dihapus_pada')
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama']);

        $konfigurasiForm = $revisiTerbaru?->konfigurasi ?? DefaultRecommendationConfig::bawaan();

        return view('master.matrix-recommendation-config', [
            'versiMatriks' => $versiMatriks,
            'revisiTerbaru' => $revisiTerbaru,
            'riwayat' => $riwayat,
            'jumlahAsesmenTerdampak' => $jumlahAsesmenTerdampak,
            'kelompokKompetensi' => $kelompokKompetensi,
            'konfigurasiForm' => $konfigurasiForm,
        ]);
    }

    public function store(
        StoreMatrixRecommendationConfigRequest $request,
        MatrixVersion $versiMatriks,
        RecommendationConfigService $configService,
    ): RedirectResponse {
        $konfigurasi = $configService->bangunKonfigurasiDariInput($request->validated());
        $revisiLama = $configService->revisiTerbaru($versiMatriks);
        $jumlahAsesmenTerdampak = $configService->hitungJumlahAsesmenTerdampak($versiMatriks->id);

        $revisi = $configService->simpanRevisiBaru(
            $versiMatriks,
            $konfigurasi,
            (string) $request->input('ringkasan_perubahan'),
            $request->user()?->id,
        );

        CatatAktivitas::catat(
            $request->user(),
            'master.konfigurasi_rekomendasi.disimpan',
            MatrixVersion::class,
            $versiMatriks->id,
            [
                'id_versi_matriks' => $versiMatriks->id,
                'kode_versi' => $versiMatriks->kode_versi,
                'id_revisi_konfigurasi' => $revisi->id,
                'nomor_revisi' => $revisi->nomor_revisi,
                'jumlah_asesmen_terdampak' => $jumlahAsesmenTerdampak,
                'ringkasan_perubahan' => $revisi->ringkasan_perubahan,
                'diff_ringkas' => $configService->bandingkanRevisi(
                    $revisiLama?->konfigurasi,
                    $konfigurasi,
                ),
            ],
        );

        $pesan = 'Konfigurasi rekomendasi revisi #'.$revisi->nomor_revisi.' disimpan.';
        if ($jumlahAsesmenTerdampak > 0) {
            $pesan .= ' '.$jumlahAsesmenTerdampak.' asesmen memakai matriks ini — hitung ulang pratinjau integrasi agar rekomendasi agregat memakai revisi terbaru.';
        }

        return redirect()
            ->route('master.versi-matriks.konfigurasi-rekomendasi.index', $versiMatriks)
            ->with('status', $pesan);
    }
}
