<?php

namespace Database\Seeders;

use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\Participant;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__.'/data/master_seed_arrays.php';

        foreach (['BCR', 'BSV'] as $kode) {
            if (! isset($data['indicators'][$kode])) {
                $data['indicators'][$kode] = [];
                for ($i = 1; $i <= 6; $i++) {
                    $data['indicators'][$kode][$i] = "Menunggu definisi resmi level {$i} ({$kode}).";
                }
            }
        }

        $groupIds = [];
        foreach ($data['groups'] as $row) {
            $group = CompetencyGroup::query()->create([
                'kode' => $row['kode'],
                'nama' => $row['nama'],
            ]);
            $groupIds[$row['kode']] = $group->id;
        }

        $competencyIds = [];
        foreach ($data['competencies'] as $row) {
            $c = Competency::query()->create([
                'id_kelompok_kompetensi' => $groupIds[$row['kode_kelompok']],
                'kode_kompetensi' => $row['kode'],
                'nama' => $row['nama'],
                'definisi' => $row['definisi'],
                'tingkat_maksimum' => 6,
                'aktif' => true,
            ]);
            $competencyIds[$row['kode']] = $c->id;
        }

        foreach ($data['indicators'] as $kodeKompetensi => $levels) {
            $competencyId = $competencyIds[$kodeKompetensi];
            foreach ($levels as $tingkat => $text) {
                CompetencyLevel::query()->create([
                    'id_kompetensi' => $competencyId,
                    'tingkat' => $tingkat,
                    'indikator_perilaku' => $text,
                ]);
            }
        }

        $toolIds = [];
        foreach ($data['tools'] as $i => $row) {
            $t = AssessmentTool::query()->create([
                'kode' => $row['kode'],
                'nama' => $row['nama'],
                'deskripsi' => $row['deskripsi'],
                'aktif' => true,
                'urutan' => $row['urutan'] ?? ($i + 1) * 10,
            ]);
            $toolIds[$row['kode']] = $t->id;
        }

        $matrix = MatrixVersion::query()->create([
            'kode_versi' => 'KAMUS-17-DEFAULT',
            'nama_versi' => 'Kamus kompetensi 17 (baseline seed)',
            'kunci_kamus' => 'PTPN17',
            'catatan_konteks' => 'Versi awal untuk pengembangan; sesuaikan mapping per kebutuhan klien.',
            'aktif' => true,
            'bawaan' => true,
            'dipublikasikan_pada' => now(),
        ]);

        $beiId = $toolIds['BEI'];
        foreach ($competencyIds as $compId) {
            CompetencyToolMapping::query()->create([
                'id_versi_matriks' => $matrix->id,
                'id_kompetensi' => $compId,
                'id_alat_penilaian' => $beiId,
                'wajib' => true,
                'bobot' => 1,
                'aktif' => true,
            ]);
        }

        $paId = $toolIds['PA'];
        foreach ($competencyIds as $compId) {
            CompetencyToolMapping::query()->create([
                'id_versi_matriks' => $matrix->id,
                'id_kompetensi' => $compId,
                'id_alat_penilaian' => $paId,
                'wajib' => false,
                'bobot' => 0.5,
                'aktif' => true,
            ]);
        }

        Participant::query()->create([
            'kode_peserta' => 'DEMO-001',
            'nama_lengkap' => 'Peserta Demo',
            'alamat_surel' => 'peserta@example.com',
            'id_versi_matriks' => $matrix->id,
            'aktif' => true,
        ]);
    }
}
