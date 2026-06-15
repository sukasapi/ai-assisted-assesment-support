<?php

namespace Database\Seeders;

use App\Models\MatrixVersion;
use App\Models\RecommendationConfigRevision;
use App\Support\DefaultRecommendationConfig;
use Illuminate\Database\Seeder;

class DefaultMatrixRecommendationConfigSeeder extends Seeder
{
    public function run(): void
    {
        MatrixVersion::query()
            ->whereNull('dihapus_pada')
            ->each(function (MatrixVersion $versi): void {
                $sudahAda = RecommendationConfigRevision::query()
                    ->where('id_versi_matriks', $versi->id)
                    ->exists();

                if ($sudahAda) {
                    return;
                }

                RecommendationConfigRevision::query()->create([
                    'id_versi_matriks' => $versi->id,
                    'nomor_revisi' => 1,
                    'konfigurasi' => DefaultRecommendationConfig::bawaan(),
                    'ringkasan_perubahan' => 'Revisi awal — konfigurasi bawaan sistem.',
                    'id_pengguna_pembuat' => null,
                ]);
            });
    }
}
