<?php

namespace App\Services\Recommendation;

use App\Models\Assessment;
use App\Models\Competency;
use App\Models\CompetencyIntegration;
use App\Support\MandatoryCompetencyCoverage;
use Illuminate\Support\Collection;

class RecommendationEvaluator
{
    /**
     * @param  Collection<int, CompetencyIntegration>  $barisIntegrasi
     * @param  array<string, mixed>  $konfigurasi
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi  keyed by id_kompetensi
     * @param  Collection<int, int>  $idKompetensiWajib
     * @return array{
     *   kode: string,
     *   label: string,
     *   dimensi: array<string, array{label: string, lolos: bool, pelanggaran: list<string>}>
     * }
     */
    public function evaluasi(
        Collection $barisIntegrasi,
        array $konfigurasi,
        Collection $metaKompetensi,
        Collection $idKompetensiWajib,
    ): array {
        $hasilAgregat = $konfigurasi['hasil_agregat'] ?? [];
        $logikaAgregat = (string) ($hasilAgregat['logika'] ?? 'and');
        $labelQualified = (string) ($hasilAgregat['label_qualified'] ?? 'Memenuhi Persyaratan (Qualified)');
        $labelNotQualified = (string) ($hasilAgregat['label_not_qualified'] ?? 'Belum Memenuhi Persyaratan (Not Qualified)');

        $dimensiHasil = [];
        $lolosSemua = $logikaAgregat === 'or' ? false : true;

        foreach ($konfigurasi['dimensi'] ?? [] as $dimensi) {
            if (! is_array($dimensi)) {
                continue;
            }

            $kodeDimensi = (string) ($dimensi['kode'] ?? '');
            $labelDimensi = (string) ($dimensi['label'] ?? $kodeDimensi);
            $filterKelompok = array_map('strval', $dimensi['filter_kelompok_kode'] ?? []);
            $kriteria = $dimensi['kriteria_qualified'] ?? [];

            $idKompetensiDimensi = $this->idKompetensiUntukDimensi(
                $metaKompetensi,
                $filterKelompok,
                $idKompetensiWajib,
            );

            $barisDimensi = $barisIntegrasi->filter(
                fn (CompetencyIntegration $r): bool => $idKompetensiDimensi->contains((int) $r->id_kompetensi),
            );

            $pelanggaran = $this->evaluasiDimensi(
                $barisDimensi,
                $idKompetensiDimensi,
                $metaKompetensi,
                is_array($kriteria) ? $kriteria : [],
            );

            $lolosDimensi = $pelanggaran === [];
            $dimensiHasil[$kodeDimensi] = [
                'label' => $labelDimensi,
                'lolos' => $lolosDimensi,
                'pelanggaran' => $pelanggaran,
            ];

            if ($logikaAgregat === 'or') {
                $lolosSemua = $lolosSemua || $lolosDimensi;
            } else {
                $lolosSemua = $lolosSemua && $lolosDimensi;
            }
        }

        return [
            'kode' => $lolosSemua ? 'qualified' : 'not_qualified',
            'label' => $lolosSemua ? $labelQualified : $labelNotQualified,
            'dimensi' => $dimensiHasil,
        ];
    }

    /**
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi
     * @param  list<string>  $filterKelompok
     * @param  Collection<int, int>  $idKompetensiWajib
     * @return Collection<int, int>
     */
    private function idKompetensiUntukDimensi(
        Collection $metaKompetensi,
        array $filterKelompok,
        Collection $idKompetensiWajib,
    ): Collection {
        if ($filterKelompok === []) {
            return $idKompetensiWajib->values();
        }

        return $metaKompetensi
            ->filter(function (array $meta, int $id) use ($filterKelompok, $idKompetensiWajib): bool {
                $kodeKelompok = (string) ($meta['kode_kelompok'] ?? '');

                return in_array($kodeKelompok, $filterKelompok, true)
                    && $idKompetensiWajib->contains($id);
            })
            ->keys()
            ->map(fn ($id): int => (int) $id)
            ->values();
    }

    /**
     * @param  Collection<int, CompetencyIntegration>  $barisDimensi
     * @param  Collection<int, int>  $idKompetensiDimensi
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi
     * @param  array<string, mixed>  $kriteria
     * @return list<string>
     */
    private function evaluasiDimensi(
        Collection $barisDimensi,
        Collection $idKompetensiDimensi,
        Collection $metaKompetensi,
        array $kriteria,
    ): array {
        $logika = (string) ($kriteria['logika'] ?? 'and');
        $aturan = is_array($kriteria['aturan'] ?? null) ? $kriteria['aturan'] : [];

        // Prasyarat data (selalu wajib): kompetensi wajib tanpa data integrasi = pelanggaran keras,
        // berlaku terlepas dari logika and/or antar aturan.
        $pelanggaranWajib = [];
        $idTercakup = $barisDimensi->pluck('id_kompetensi')->map(fn ($id): int => (int) $id);
        foreach ($idKompetensiDimensi as $idKompetensi) {
            if (! $idTercakup->contains((int) $idKompetensi)) {
                $kode = (string) ($metaKompetensi->get((int) $idKompetensi)['kode_kompetensi'] ?? (string) $idKompetensi);
                $pelanggaranWajib[] = "Kompetensi wajib {$kode} belum memiliki data integrasi (PK disahkan).";
            }
        }

        // Evaluasi tiap aturan secara terpisah (kumpulkan pelanggaran per-aturan).
        $hasilPerAturan = [];
        foreach ($aturan as $aturanItem) {
            if (! is_array($aturanItem)) {
                continue;
            }
            $hasilPerAturan[] = $this->evaluasiAturan($aturanItem, $barisDimensi, $metaKompetensi);
        }

        // Gabungkan menurut logika antar aturan.
        $pelanggaranAturan = [];
        if ($hasilPerAturan !== []) {
            if ($logika === 'or') {
                // OR: lolos jika ADA minimal satu aturan tanpa pelanggaran.
                $adaAturanLolos = false;
                foreach ($hasilPerAturan as $pelanggaranAturanItem) {
                    if ($pelanggaranAturanItem === []) {
                        $adaAturanLolos = true;
                        break;
                    }
                }
                if (! $adaAturanLolos) {
                    foreach ($hasilPerAturan as $pelanggaranAturanItem) {
                        $pelanggaranAturan = array_merge($pelanggaranAturan, $pelanggaranAturanItem);
                    }
                }
            } else {
                // AND (default): semua aturan harus lolos; laporkan pelanggaran aturan yang gagal.
                foreach ($hasilPerAturan as $pelanggaranAturanItem) {
                    $pelanggaranAturan = array_merge($pelanggaranAturan, $pelanggaranAturanItem);
                }
            }
        }

        return array_values(array_unique(array_merge($pelanggaranWajib, $pelanggaranAturan)));
    }

    /**
     * Evaluasi satu aturan, kembalikan daftar pelanggarannya (kosong = aturan terpenuhi).
     *
     * @param  array<string, mixed>  $aturanItem
     * @param  Collection<int, CompetencyIntegration>  $barisDimensi
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi
     * @return list<string>
     */
    private function evaluasiAturan(array $aturanItem, Collection $barisDimensi, Collection $metaKompetensi): array
    {
        $jenis = (string) ($aturanItem['jenis'] ?? '');
        $tingkat = (int) ($aturanItem['tingkat'] ?? 0);
        $pelanggaran = [];

        match ($jenis) {
            'forbid_tingkat' => $this->terapkanForbidTingkat($barisDimensi, $metaKompetensi, $tingkat, $pelanggaran),
            'max_count_tingkat' => $this->terapkanMaxCountTingkat(
                $barisDimensi,
                $metaKompetensi,
                $tingkat,
                (int) ($aturanItem['maksimum'] ?? 0),
                $pelanggaran,
            ),
            'min_tingkat_semua' => $this->terapkanMinTingkatSemua($barisDimensi, $metaKompetensi, $tingkat, $pelanggaran),
            default => null,
        };

        return array_values($pelanggaran);
    }

    /**
     * @param  Collection<int, CompetencyIntegration>  $barisDimensi
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi
     * @param  list<string>  $pelanggaran
     */
    private function terapkanForbidTingkat(
        Collection $barisDimensi,
        Collection $metaKompetensi,
        int $tingkat,
        array &$pelanggaran,
    ): void {
        foreach ($barisDimensi as $baris) {
            if ((int) ($baris->tingkat_tercapai ?? 0) === $tingkat) {
                $kode = (string) ($metaKompetensi->get((int) $baris->id_kompetensi)['kode_kompetensi'] ?? (string) $baris->id_kompetensi);
                $pelanggaran[] = "Kompetensi {$kode} memiliki tingkat tercapai {$tingkat} (tidak diperbolehkan).";
            }
        }
    }

    /**
     * @param  Collection<int, CompetencyIntegration>  $barisDimensi
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi
     * @param  list<string>  $pelanggaran
     */
    private function terapkanMaxCountTingkat(
        Collection $barisDimensi,
        Collection $metaKompetensi,
        int $tingkat,
        int $maksimum,
        array &$pelanggaran,
    ): void {
        $jumlah = $barisDimensi->filter(
            fn (CompetencyIntegration $r): bool => (int) ($r->tingkat_tercapai ?? 0) === $tingkat,
        )->count();

        if ($jumlah > $maksimum) {
            $pelanggaran[] = "Jumlah kompetensi dengan tingkat tercapai {$tingkat} adalah {$jumlah}, melebihi maksimum {$maksimum}.";
        }
    }

    /**
     * @param  Collection<int, CompetencyIntegration>  $barisDimensi
     * @param  Collection<int, array{kode_kompetensi: string, kode_kelompok: string|null}>  $metaKompetensi
     * @param  list<string>  $pelanggaran
     */
    private function terapkanMinTingkatSemua(
        Collection $barisDimensi,
        Collection $metaKompetensi,
        int $tingkatMin,
        array &$pelanggaran,
    ): void {
        foreach ($barisDimensi as $baris) {
            $tercapai = (int) ($baris->tingkat_tercapai ?? 0);
            if ($tercapai < $tingkatMin) {
                $kode = (string) ($metaKompetensi->get((int) $baris->id_kompetensi)['kode_kompetensi'] ?? (string) $baris->id_kompetensi);
                $pelanggaran[] = "Kompetensi {$kode} tingkat tercapai {$tercapai} di bawah minimum {$tingkatMin}.";
            }
        }
    }

    /**
     * @return Collection<int, array{id: int, kode_kompetensi: string, kode_kelompok: string|null}>
     */
    public static function metaKompetensiUntukAsesmen(Assessment $asesmen): Collection
    {
        $idKompetensiWajib = MandatoryCompetencyCoverage::idKompetensiWajib($asesmen);

        if ($idKompetensiWajib->isEmpty()) {
            return collect();
        }

        return Competency::query()
            ->whereIn('id', $idKompetensiWajib->all())
            ->whereNull('dihapus_pada')
            ->with('group')
            ->get()
            ->mapWithKeys(fn (Competency $k): array => [
                (int) $k->id => [
                    'id' => (int) $k->id,
                    'kode_kompetensi' => (string) $k->kode_kompetensi,
                    'kode_kelompok' => $k->group?->kode !== null ? (string) $k->group->kode : null,
                ],
            ]);
    }
}
