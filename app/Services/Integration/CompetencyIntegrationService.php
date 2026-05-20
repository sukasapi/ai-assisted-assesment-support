<?php

namespace App\Services\Integration;

use App\Enums\AssessmentPurpose;
use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\Competency;
use App\Models\CompetencyIntegration;
use App\Models\KeyBehavior;
use App\Support\KeyBehaviorPresentation;
use App\Support\MandatoryCompetencyCoverage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompetencyIntegrationService
{
    public const VERSI_PERHITUNGAN = 'v1';

    /** Level target default bila belum ada RCL per kamus (MVP). */
    public const TARGET_DEFAULT_TALENT = 4;

    /**
     * @return array{
     *   berhasil: bool,
     *   pesan?: string,
     *   jumlah_kompetensi?: int,
     *   job_fit_persen?: float|null,
     *   rekomendasi_agregat?: string|null
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
            ]);

            return [
                'berhasil' => true,
                'pesan' => 'Belum ada perilaku kunci disahkan yang layak integrasi.',
                'jumlah_kompetensi' => 0,
                'job_fit_persen' => null,
                'rekomendasi_agregat' => null,
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

        DB::transaction(function () use ($asesmen, $barisIntegrasi, $idKompetensiWajib, $sekarang, $idPenggunaPemicu): void {
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

            $jobFit = $this->hitungJobFitPersen($asesmen->id, $idKompetensiWajib);

            $asesmen->update([
                'job_fit_persen_pratinjau' => $jobFit,
                'integrasi_pratinjau_pada' => $sekarang,
                'status' => AssessmentStatus::Terintegrasi,
            ]);
        });

        $rekomendasiAgregat = $this->rekomendasiAgregat($asesmen->id, $idKompetensiWajib);
        $jobFit = Assessment::query()->whereKey($asesmen->id)->value('job_fit_persen_pratinjau');

        return [
            'berhasil' => true,
            'jumlah_kompetensi' => count($barisIntegrasi),
            'job_fit_persen' => $jobFit !== null ? (float) $jobFit : null,
            'rekomendasi_agregat' => $rekomendasiAgregat,
        ];
    }

    /**
     * @param  Collection<int, KeyBehavior>  $pkGrup
     * @param  Collection<string, \App\Models\CompetencyToolMapping>  $pemetaan
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
        $kontribusiPerAlat = [];
        $detailBobot = [];
        $jumlahPk = $pkGrup->count();

        $pkPerAlat = $pkGrup->groupBy('id_alat_penilaian');

        foreach ($pkPerAlat as $idAlat => $pkAlat) {
            $kunci = $kompetensi->id.'-'.$idAlat;
            $map = $pemetaan->get($kunci);
            if ($map === null) {
                continue;
            }

            $bobot = (float) $map->bobot;
            if ($bobot <= 0) {
                continue;
            }

            $levelMax = $pkAlat
                ->map(fn (KeyBehavior $pk): int => (int) ($pk->competencyLevel?->tingkat ?? 0))
                ->filter(fn (int $l): bool => $l > 0)
                ->max() ?? 0;

            if ($levelMax <= 0) {
                continue;
            }

            $kontribusiPerAlat[(int) $idAlat] = [
                'level' => $levelMax,
                'bobot' => $bobot,
                'kontribusi' => $levelMax * $bobot,
                'kode_alat' => (string) ($map->tool?->kode ?? (string) $idAlat),
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
            ];
        }

        $tingkatTarget = $this->tentukanTingkatTarget($asesmen, $tingkatTercapai, $maxLevel);
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

    private function tentukanTingkatTarget(Assessment $asesmen, int $tingkatTercapai, int $maxLevel): int
    {
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
     * @param  Collection<int, int>  $idKompetensiWajib
     */
    private function hitungJobFitPersen(int $idAsesmen, Collection $idKompetensiWajib): ?float
    {
        if ($idKompetensiWajib->isEmpty()) {
            return null;
        }

        $baris = CompetencyIntegration::query()
            ->where('id_asesmen', $idAsesmen)
            ->whereIn('id_kompetensi', $idKompetensiWajib->all())
            ->get();

        if ($baris->isEmpty()) {
            return null;
        }

        $totalCapaian = $baris->sum(fn (CompetencyIntegration $r): int => (int) ($r->tingkat_tercapai ?? 0));
        $totalTarget = $baris->sum(fn (CompetencyIntegration $r): int => (int) ($r->tingkat_target ?? 0));

        if ($totalTarget <= 0) {
            return null;
        }

        return round(($totalCapaian / $totalTarget) * 100, 2);
    }

    /**
     * @param  Collection<int, int>  $idKompetensiWajib
     */
    private function rekomendasiAgregat(int $idAsesmen, Collection $idKompetensiWajib): ?string
    {
        if ($idKompetensiWajib->isEmpty()) {
            return null;
        }

        $barisWajib = CompetencyIntegration::query()
            ->where('id_asesmen', $idAsesmen)
            ->whereIn('id_kompetensi', $idKompetensiWajib->all())
            ->get();

        if ($barisWajib->isEmpty()) {
            return null;
        }

        $jobFit = $this->hitungJobFitPersen($idAsesmen, $idKompetensiWajib) ?? 0.0;
        $gapBesar = $barisWajib->where(fn (CompetencyIntegration $r): bool => (int) ($r->selisih_gap ?? 0) >= 2)->count();
        $gapSatu = $barisWajib->where(fn (CompetencyIntegration $r): bool => (int) ($r->selisih_gap ?? 0) === 1)->count();

        if ($jobFit >= 85.0 && $gapBesar === 0) {
            return 'fit';
        }
        if ($jobFit >= 60.0 || $gapSatu > 0) {
            return 'development';
        }

        return 'not_fit';
    }
}
