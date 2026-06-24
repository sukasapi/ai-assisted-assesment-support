<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\CompetencyIntegration;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixCompetencyTarget;
use App\Models\User;
use App\Services\Integration\CompetencyIntegrationService;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class MatrixCompetencyTargetTest extends TestCase
{
    public function test_target_profil_jabatan_dipakai_alih_alih_heuristik(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        // Tentukan target jabatan = 5 untuk semua kompetensi matriks default sebelum asesmen dibuat.
        $idVersi = CompetencyToolMapping::query()->value('id_versi_matriks');
        $idKompetensiMatriks = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $idVersi)
            ->pluck('id_kompetensi')
            ->unique();
        foreach ($idKompetensiMatriks as $idKompetensi) {
            MatrixCompetencyTarget::query()->create([
                'id_versi_matriks' => $idVersi,
                'id_kompetensi' => (int) $idKompetensi,
                'tingkat_target' => 5,
            ]);
        }

        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $this->isiPkDisahkanLevel($asesmen, $admin, 3);
        app(CompetencyIntegrationService::class)->hitungUlang($asesmen->fresh(), $admin->id);

        $baris = CompetencyIntegration::query()->where('id_asesmen', $asesmen->id)->get();
        $this->assertNotEmpty($baris);
        // Target harus 5 (dari profil), bukan 4 (heuristik promosi capaian+1).
        foreach ($baris as $r) {
            $this->assertSame(5, (int) $r->tingkat_target, 'Target harus mengikuti profil jabatan (5).');
        }
    }

    private function isiPkDisahkanLevel(Assessment $asesmen, User $admin, int $tingkat): void
    {
        $idAlatAktif = $asesmen->toolSelections()->where('aktif', true)->pluck('id_alat_penilaian')->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique();

        $alat = AssessmentTool::query()->whereIn('id', $idAlatAktif)->orderBy('id')->firstOrFail();

        foreach ($idKompetensiWajib as $idKompetensi) {
            $idTingkat = CompetencyLevel::query()
                ->where('id_kompetensi', $idKompetensi)
                ->where('tingkat', $tingkat)
                ->whereNull('dihapus_pada')
                ->value('id')
                ?? CompetencyLevel::query()
                    ->where('id_kompetensi', $idKompetensi)
                    ->whereNull('dihapus_pada')
                    ->orderBy('tingkat')
                    ->value('id');
            if ($idTingkat === null) {
                continue;
            }
            $asesmen->keyBehaviors()->updateOrCreate(
                ['id_alat_penilaian' => $alat->id, 'id_kompetensi' => (int) $idKompetensi],
                [
                    'id_tingkat_kompetensi' => (int) $idTingkat,
                    'teks_perilaku' => 'PK uji target',
                    'tervalidasi' => true,
                    'id_pengguna_validasi' => $admin->id,
                    'waktu_validasi' => now(),
                ],
            );
        }
    }
}
