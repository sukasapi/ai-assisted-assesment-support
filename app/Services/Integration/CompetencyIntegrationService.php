<?php

namespace App\Services\Integration;

use App\Enums\AssessmentPurpose;
use App\Enums\AssessmentStatus;
use App\Enums\AssessmentToolAggregationStrategy;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\CompetencyIntegration;
use App\Models\CompetencyToolMapping;
use App\Models\KeyBehavior;
use App\Services\Recommendation\RecommendationConfigService;
use App\Services\Recommendation\RecommendationEvaluator;
use App\Support\KeyBehaviorPresentation;
use App\Support\MandatoryCompetencyCoverage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompetencyIntegrationService
{
    public const VERSI_PERHITUNGAN = 'v1';

    /** Level target default bila belum ada RCL per kamus (MVP). */
    public const TARGET_DEFAULT_TALENT = 4;

    public function __construct(
        private readonly RecommendationConfigService $recommendationConfigService,
        private readonly RecommendationEvaluator $recommendationEvaluator,
    ) {}

    /**
     * @return array{
     *   berhasil: bool,
     *   pesan?: string,
     *   jumlah_kompetensi?: int,
     *   job_fit_persen?: float|null,
     *   rekomendasi_agregat?: string|null,
     *   kode_rekomendasi_agregat?: string|null,
     *   id_revisi_konfigurasi?: int|null,
     *   nomor_revisi_konfigurasi?: int|null
     * }
     */
    public function hitungUlang(Assessment $asesmen, ?int $idPenggunaPemicu = null): array
    {
        if ($asesmen->status === AssessmentStatus::SelesaiFinal) {
            return [
                'berhasil' => false,
                'pesan' => 'Asesmen sudah difinalisasi. Batalkan finalisasi terlebih dahulu untuk menghitung ulang pratinjau.',
            ];
        }

        $pkLayak = MandatoryCompetencyCoverage::queryPkLayak($asesmen)
            ->with(['competencyLevel', 'tool', 'evidence', 'competency'])
            ->get();

        if ($pkLayak->isEmpty()) {
            CompetencyIntegration::query()->where('id_asesmen', $asesmen->id)->delete();
            $asesmen->update([
                'job_fit_persen_pratinjau' => null,
                'integrasi_pratinjau_pada' => null,
                'id_revisi_konfigurasi_terakhir' => null,
                'kode_rekomendasi_agregat' => null,
                'detail_rekomendasi_agregat' => null,
            ]);

            return [
                'berhasil' => true,
                'pesan' => 'Belum ada perilaku kunci disahkan yang layak integrasi.',
                'jumlah_kompetensi' => 0,
                'job_fit_persen' => null,
                'rekomendasi_agregat' => null,
                'kode_rekomendasi_agregat' => null,
                'id_revisi_konfigurasi' => null,
                'nomor_revisi_konfigurasi' => null,
            ];
        }

        $pemetaan = MandatoryCompetencyCoverage::pemetaanTerindeks($asesmen);
        $idDariBulk = KeyBehaviorPresentation::idDariAnalisisBulk($asesmen);

        $pkPerKompetensi = $pkLayak->groupBy('id_kompetensi');
        $barisIntegrasi = [];
        $idKompetensiWajib = MandatoryCompetencyCoverage::idKompetensiWajib($asesmen);
        $sekarang = now();

        foreach ($pkPerKompetensi as $idKompetensi => $pkGrup) {
            /** @var Collection<int, KeyBehavior> $pkGrup */
            $kompetensi = $pkGrup->first()?->competency;
            if ($kompetensi === null) {
                $kompetensi = Competency::query()->find($idKompetensi);
            }
            if ($kompetensi === null) {
                continue;
            }

            $hasil = $this->agregasiKompetensi($asesmen, $kompetensi, $pkGrup, $pemetaan, $idDariBulk);
            if ($hasil === null) {
                continue;
            }

            $barisIntegrasi[] = array_merge($hasil, [
                'id_asesmen' => $asesmen->id,
                'id_kompetensi' => (int) $idKompetensi,
                'versi_perhitungan' => self::VERSI_PERHITUNGAN,
                'dihitung_pada' => $sekarang,
                'id_pengguna_pemicu' => $idPenggunaPemicu,
            ]);
        }

        DB::transaction(function () use ($asesmen, $barisIntegrasi, $idKompetensiWajib, $sekarang): void {
            $idKompetensiTerhitung = collect($barisIntegrasi)->pluck('id_kompetensi')->all();

            CompetencyIntegration::query()
                ->where('id_asesmen', $asesmen->id)
                ->when($idKompetensiTerhitung !== [], function ($q) use ($idKompetensiTerhitung): void {
                    $q->whereNotIn('id_kompetensi', $idKompetensiTerhitung);
                })
                ->delete();

            foreach ($barisIntegrasi as $baris) {
                CompetencyIntegration::query()->updateOrCreate(
                    [
                        'id_asesmen' => $asesmen->id,
                        'id_kompetensi' => $baris['id_kompetensi'],
                    ],
                    $baris,
                );
            }

            $jobFit = $this->hitungJobFitPersen($asesmen, $idKompetensiWajib);
            $hasilRekomendasi = $this->evaluasiRekomendasiAgregat($asesmen, $idKompetensiWajib);

            $asesmen->update([
                'job_fit_persen_pratinjau' => $jobFit,
                'integrasi_pratinjau_pada' => $sekarang,
                'status' => AssessmentStatus::Terintegrasi,
                'id_revisi_konfigurasi_terakhir' => $hasilRekomendasi['id_revisi'] ?? null,
                'kode_rekomendasi_agregat' => $hasilRekomendasi['kode'] ?? null,
                'detail_rekomendasi_agregat' => $hasilRekomendasi['detail'] ?? null,
            ]);
        });

        $rekomendasiAgregat = $this->rekomendasiAgregat($asesmen, $idKompetensiWajib);
        $jobFit = Assessment::query()->whereKey($asesmen->id)->value('job_fit_persen_pratinjau');
        $asesmen->refresh();
        $asesmen->load('lastRecommendationConfigRevision');

        return [
            'berhasil' => true,
            'jumlah_kompetensi' => count($barisIntegrasi),
            'job_fit_persen' => $jobFit !== null ? (float) $jobFit : null,
            'rekomendasi_agregat' => $rekomendasiAgregat,
            'kode_rekomendasi_agregat' => $asesmen->kode_rekomendasi_agregat,
            'id_revisi_konfigurasi' => $asesmen->id_revisi_konfigurasi_terakhir,
            'nomor_revisi_konfigurasi' => $asesmen->lastRecommendationConfigRevision?->nomor_revisi,
        ];
    }

    /**
     * @param  Collection<int, int>  $idKompetensiWajib
     * @return array{id_revisi?: int, kode?: string, detail?: array<string, mixed>}
     */
    private function evaluasiRekomendasiAgregat(Assessment $asesmen, Collection $idKompetensiWajib): array
    {
        $revisi = $this->recommendationConfigService->revisiTerbaru((int) $asesmen->id_versi_matriks);
        if ($revisi === null) {
            return [];
        }

        $barisIntegrasi = CompetencyIntegration::query()
            ->where('id_asesmen', $asesmen->id)
            ->get();

        $metaKompetensi = $this->recommendationEvaluator::metaKompetensiUntukAsesmen($asesmen);
        $hasil = $this->recommendationEvaluator->evaluasi(
            $barisIntegrasi,
            $revisi->konfigurasi ?? [],
            $metaKompetensi,
            $idKompetensiWajib,
        );

        return [
            'id_revisi' => $revisi->id,
            'kode' => $hasil['kode'],
            'detail' => $hasil,
        ];
    }

    /**
     * @param  Collection<int, KeyBehavior>  $pkGrup
     * @param  Collection<string, CompetencyToolMapping>  $pemetaan
     * @return array<string, mixed>|null
     */
    private function agregasiKompetensi(
        Assessment $asesmen,
        Competency $kompetensi,
        Collection $pkGrup,
        Collection $pemetaan,
        Collection $idDariBulk,
    ): ?array {
        $maxLevel = max(1, (int) ($kompetensi->tingkat_maksimum ?? 6));
        $strategi = $this->strategiAgregasi($asesmen);
        $kontribusiPerAlat = [];
        $detailBobot = [];
        $jumlahPk = $pkGrup->count();
        $targetProfil = null;

        $pkPerAlat = $pkGrup->groupBy('id_alat_penilaian');

        foreach ($pkPerAlat as $idAlat => $pkAlat) {
            $kunci = $kompetensi->id.'-'.$idAlat;
            $map = $pemetaan->get($kunci);
            if ($map === null) {
                continue;
            }

            // Target jabatan beku (bila ada) — sama untuk semua alat satu kompetensi.
            if ($targetProfil === null && ! empty($map->tingkat_target)) {
                $targetProfil = (int) $map->tingkat_target;
            }

            $bobot = (float) $map->bobot;
            if ($bobot <= 0) {
                continue;
            }

            $levelTiap = $pkAlat
                ->map(fn (KeyBehavior $pk): int => (int) ($pk->competencyLevel?->tingkat ?? 0))
                ->filter(fn (int $l): bool => $l > 0)
                ->values();

            if ($levelTiap->isEmpty()) {
                continue;
            }

            // L-3: gabungkan beberapa PK satu alat sesuai strategi yang dipilih asesor.
            $levelAlat = $strategi === AssessmentToolAggregationStrategy::RataRata
                ? (int) max(1, min($maxLevel, (int) round($levelTiap->avg())))
                : (int) $levelTiap->max();

            $kontribusiPerAlat[(int) $idAlat] = [
                'level' => $levelAlat,
                'bobot' => $bobot,
                'kontribusi' => $levelAlat * $bobot,
                'kode_alat' => (string) ($map->tool?->kode ?? (string) $idAlat),
                'jumlah_pk' => $levelTiap->count(),
                'level_min' => (int) $levelTiap->min(),
                'level_max' => (int) $levelTiap->max(),
            ];
        }

        if ($kontribusiPerAlat === []) {
            return null;
        }

        $totalBobot = array_sum(array_column($kontribusiPerAlat, 'bobot'));
        $totalKontribusi = array_sum(array_column($kontribusiPerAlat, 'kontribusi'));
        $skorTertimbang = $totalBobot > 0 ? $totalKontribusi / $totalBobot : 0.0;
        $tingkatTercapai = (int) max(1, min($maxLevel, (int) round($skorTertimbang)));

        foreach ($kontribusiPerAlat as $row) {
            $detailBobot[$row['kode_alat']] = [
                'level' => $row['level'],
                'bobot' => round($row['bobot'], 4),
                'kontribusi' => round($row['kontribusi'], 4),
                'jumlah_pk' => $row['jumlah_pk'],
                'level_min' => $row['level_min'],
                'level_max' => $row['level_max'],
                'strategi' => $strategi->value,
            ];
        }

        $tingkatTarget = $this->tentukanTingkatTarget($asesmen, $tingkatTercapai, $maxLevel, $targetProfil);
        $selisihGap = $tingkatTarget - $tingkatTercapai;
        $rekomendasiKode = $this->rekomendasiPerKompetensi($selisihGap);

        return [
            'tingkat_target' => $tingkatTarget,
            'tingkat_tercapai' => $tingkatTercapai,
            'skor_terbobot' => round($skorTertimbang, 4),
            'selisih_gap' => $selisihGap,
            'rekomendasi_kode' => $rekomendasiKode,
            'rekomendasi_teks' => $this->labelRekomendasi($rekomendasiKode),
            'jumlah_pk_masuk' => $jumlahPk,
            'detail_bobot' => $detailBobot,
            'sumber_utama' => $this->tentukanSumberUtama($pkGrup, $idDariBulk),
        ];
    }

    private function strategiAgregasi(Assessment $asesmen): AssessmentToolAggregationStrategy
    {
        if ($asesmen->strategi_agregasi_alat instanceof AssessmentToolAggregationStrategy) {
            return $asesmen->strategi_agregasi_alat;
        }

        return AssessmentToolAggregationStrategy::tryFrom(
            (string) config('integrasi.strategi_agregasi_alat_default', 'max')
        ) ?? AssessmentToolAggregationStrategy::Maksimum;
    }

    private function tentukanTingkatTarget(Assessment $asesmen, int $tingkatTercapai, int $maxLevel, ?int $targetProfil = null): int
    {
        // Feature: target dari profil jabatan (matriks) bila didefinisikan — menggantikan heuristik.
        if ($targetProfil !== null && $targetProfil > 0) {
            return min(max($targetProfil, 1), $maxLevel);
        }

        if ($asesmen->tujuan === AssessmentPurpose::Promosi) {
            return min(max($tingkatTercapai, 1) + 1, $maxLevel);
        }

        return min(max(self::TARGET_DEFAULT_TALENT, $tingkatTercapai), $maxLevel);
    }

    /**
     * @param  Collection<int, KeyBehavior>  $pkGrup
     */
    private function tentukanSumberUtama(Collection $pkGrup, Collection $idDariBulk): string
    {
        $adaManual = false;
        $adaInkremental = false;
        $adaBulk = false;

        foreach ($pkGrup as $pk) {
            if ($idDariBulk->contains((int) $pk->id)) {
                $adaBulk = true;

                continue;
            }
            if ($pk->id_bukti_penilaian !== null && ($pk->evidence?->ai_dinilai_pada !== null || $pk->evidence?->ai_tingkat !== null)) {
                $adaInkremental = true;

                continue;
            }
            if ($pk->id_bukti_penilaian === null && ! $idDariBulk->contains((int) $pk->id)) {
                $adaManual = true;
            }
        }

        $jenis = array_filter([
            $adaManual ? 'manual' : null,
            $adaInkremental ? 'ai_incremental' : null,
            $adaBulk ? 'ai_bulk' : null,
        ]);

        if (count($jenis) > 1) {
            return 'campuran';
        }

        return $jenis[0] ?? 'manual';
    }

    private function rekomendasiPerKompetensi(int $selisihGap): string
    {
        if ($selisihGap <= 0) {
            return 'fit';
        }
        if ($selisihGap === 1) {
            return 'development';
        }

        return 'not_fit';
    }

    private function labelRekomendasi(string $kode): string
    {
        return match ($kode) {
            'fit' => 'Fit — capaian memenuhi atau melampaui target pratinjau.',
            'development' => 'Development — masih ada jarak satu tingkat terhadap target pratinjau.',
            default => 'Not Fit — jarak terhadap target pratinjau ≥ 2 tingkat.',
        };
    }

    /**
     * Job Fit % = Σ tingkat tercapai / Σ tingkat target, atas SELURUH kompetensi wajib.
     *
     * L-2: kompetensi wajib yang belum punya bukti (tanpa baris integrasi) tetap diperhitungkan
     * sebagai capaian 0 terhadap target standarnya, sehingga cakupan tidak lengkap menurunkan
     * Job Fit (mencegah skor "palsu tinggi" saat banyak kompetensi wajib kosong).
     *
     * @param  Collection<int, int>  $idKompetensiWajib
     */
    private function hitungJobFitPersen(Assessment $asesmen, Collection $idKompetensiWajib): ?float
    {
        if ($idKompetensiWajib->isEmpty()) {
            return null;
        }

        $baris = CompetencyIntegration::query()
            ->where('id_asesmen', $asesmen->id)
            ->whereIn('id_kompetensi', $idKompetensiWajib->all())
            ->get();

        $totalCapaian = $baris->sum(fn (CompetencyIntegration $r): int => (int) ($r->tingkat_tercapai ?? 0));
        $totalTarget = $baris->sum(fn (CompetencyIntegration $r): int => (int) ($r->tingkat_target ?? 0));

        $idTerhitung = $baris->pluck('id_kompetensi')->map(fn ($id): int => (int) $id)->all();
        $idBelum = $idKompetensiWajib
            ->reject(fn ($id): bool => in_array((int) $id, $idTerhitung, true))
            ->values();

        if ($idBelum->isNotEmpty()) {
            $kompetensiBelum = Competency::query()
                ->whereIn('id', $idBelum->all())
                ->get(['id', 'tingkat_maksimum']);
            foreach ($kompetensiBelum as $kompetensi) {
                $maxLevel = max(1, (int) ($kompetensi->tingkat_maksimum ?? 6));
                $totalTarget += $this->tentukanTingkatTarget($asesmen, 0, $maxLevel);
                // capaian += 0 (belum ada bukti)
            }
        }

        if ($totalTarget <= 0) {
            return null;
        }

        return round(($totalCapaian / $totalTarget) * 100, 2);
    }

    /**
     * Rekomendasi heuristik indikatif (fit/development/not_fit).
     *
     * L-1: selaras dengan mesin aturan konfigurabel — cakupan kompetensi wajib yang belum
     * lengkap TIDAK boleh menghasilkan "fit" (minimal "development"), sehingga pesan ke
     * konsultan tidak bertentangan dengan verdict qualified/not_qualified.
     *
     * @param  Collection<int, int>  $idKompetensiWajib
     */
    private function rekomendasiAgregat(Assessment $asesmen, Collection $idKompetensiWajib): ?string
    {
        if ($idKompetensiWajib->isEmpty()) {
            return null;
        }

        $barisWajib = CompetencyIntegration::query()
            ->where('id_asesmen', $asesmen->id)
            ->whereIn('id_kompetensi', $idKompetensiWajib->all())
            ->get();

        if ($barisWajib->isEmpty()) {
            return null;
        }

        $cakupanLengkap = $barisWajib->count() >= $idKompetensiWajib->count();

        $jobFit = $this->hitungJobFitPersen($asesmen, $idKompetensiWajib) ?? 0.0;
        $gapBesar = $barisWajib->where(fn (CompetencyIntegration $r): bool => (int) ($r->selisih_gap ?? 0) >= 2)->count();
        $gapSatu = $barisWajib->where(fn (CompetencyIntegration $r): bool => (int) ($r->selisih_gap ?? 0) === 1)->count();

        if ($jobFit >= 85.0 && $gapBesar === 0 && $cakupanLengkap) {
            return 'fit';
        }
        if ($jobFit >= 60.0 || $gapSatu > 0 || ! $cakupanLengkap) {
            return 'development';
        }

        return 'not_fit';
    }
}
