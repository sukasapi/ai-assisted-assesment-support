<?php

namespace App\Services\Recommendation;

use App\Models\Assessment;
use App\Models\MatrixVersion;
use App\Models\RecommendationConfigRevision;
use Illuminate\Support\Facades\DB;

class RecommendationConfigService
{
    /**
     * @param  array<string, mixed>  $konfigurasi
     */
    public function simpanRevisiBaru(
        MatrixVersion $versiMatriks,
        array $konfigurasi,
        string $ringkasanPerubahan,
        ?int $idPenggunaPembuat = null,
    ): RecommendationConfigRevision {
        return DB::transaction(function () use ($versiMatriks, $konfigurasi, $ringkasanPerubahan, $idPenggunaPembuat): RecommendationConfigRevision {
            $nomorRevisi = (int) RecommendationConfigRevision::query()
                ->where('id_versi_matriks', $versiMatriks->id)
                ->lockForUpdate()
                ->max('nomor_revisi');

            return RecommendationConfigRevision::query()->create([
                'id_versi_matriks' => $versiMatriks->id,
                'nomor_revisi' => $nomorRevisi + 1,
                'konfigurasi' => $konfigurasi,
                'ringkasan_perubahan' => $ringkasanPerubahan,
                'id_pengguna_pembuat' => $idPenggunaPembuat,
            ]);
        });
    }

    public function revisiTerbaru(MatrixVersion|int $versiMatriks): ?RecommendationConfigRevision
    {
        $id = $versiMatriks instanceof MatrixVersion ? $versiMatriks->id : $versiMatriks;

        return RecommendationConfigRevision::query()
            ->where('id_versi_matriks', $id)
            ->orderByDesc('nomor_revisi')
            ->first();
    }

    public function hitungJumlahAsesmenTerdampak(int $idVersiMatriks): int
    {
        return Assessment::query()
            ->where('id_versi_matriks', $idVersiMatriks)
            ->count();
    }

    /**
     * @param  array<string, mixed>|null  $lama
     * @param  array<string, mixed>  $baru
     * @return list<string>
     */
    public function bandingkanRevisi(?array $lama, array $baru): array
    {
        if ($lama === null) {
            return ['Revisi awal dibuat.'];
        }

        $diff = [];

        $jumlahDimensiLama = count($lama['dimensi'] ?? []);
        $jumlahDimensiBaru = count($baru['dimensi'] ?? []);
        if ($jumlahDimensiLama !== $jumlahDimensiBaru) {
            $diff[] = "Jumlah dimensi: {$jumlahDimensiLama} → {$jumlahDimensiBaru}.";
        }

        $labelQualifiedLama = $lama['hasil_agregat']['label_qualified'] ?? null;
        $labelQualifiedBaru = $baru['hasil_agregat']['label_qualified'] ?? null;
        if ($labelQualifiedLama !== $labelQualifiedBaru) {
            $diff[] = 'Label qualified diubah.';
        }

        $labelNotQualifiedLama = $lama['hasil_agregat']['label_not_qualified'] ?? null;
        $labelNotQualifiedBaru = $baru['hasil_agregat']['label_not_qualified'] ?? null;
        if ($labelNotQualifiedLama !== $labelNotQualifiedBaru) {
            $diff[] = 'Label not qualified diubah.';
        }

        if ($diff === [] && json_encode($lama) !== json_encode($baru)) {
            $diff[] = 'Konfigurasi dimensi atau aturan diubah.';
        }

        return $diff;
    }

    /**
     * Bangun struktur konfigurasi dari input form.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function bangunKonfigurasiDariInput(array $input): array
    {
        $dimensi = [];
        foreach ($input['dimensi'] ?? [] as $baris) {
            if (! is_array($baris)) {
                continue;
            }

            $aturan = [];
            foreach ($baris['aturan'] ?? [] as $a) {
                if (! is_array($a) || empty($a['jenis'])) {
                    continue;
                }

                $item = ['jenis' => (string) $a['jenis']];
                if (isset($a['tingkat'])) {
                    $item['tingkat'] = (int) $a['tingkat'];
                }
                if (isset($a['maksimum'])) {
                    $item['maksimum'] = (int) $a['maksimum'];
                }
                $aturan[] = $item;
            }

            $dimensi[] = [
                'kode' => (string) ($baris['kode'] ?? ''),
                'label' => (string) ($baris['label'] ?? ''),
                'filter_kelompok_kode' => array_values(array_filter(
                    array_map('strval', $baris['filter_kelompok_kode'] ?? []),
                )),
                'kriteria_qualified' => [
                    'logika' => (string) ($baris['logika'] ?? 'and'),
                    'aturan' => $aturan,
                ],
            ];
        }

        return [
            'versi_skema' => 1,
            'dimensi' => $dimensi,
            'hasil_agregat' => [
                'logika' => (string) ($input['logika_agregat'] ?? 'and'),
                'label_qualified' => (string) ($input['label_qualified'] ?? 'Memenuhi Persyaratan (Qualified)'),
                'label_not_qualified' => (string) ($input['label_not_qualified'] ?? 'Belum Memenuhi Persyaratan (Not Qualified)'),
            ],
        ];
    }
}
