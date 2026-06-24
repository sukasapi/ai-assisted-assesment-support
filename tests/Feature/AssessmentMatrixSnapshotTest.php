<?php

namespace Tests\Feature;

use App\Models\AssessmentMatrixMappingSnapshot;
use App\Models\CompetencyToolMapping;
use App\Models\User;
use App\Support\MandatoryCompetencyCoverage;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class AssessmentMatrixSnapshotTest extends TestCase
{
    public function test_snapshot_dibuat_saat_asesmen_dibuat(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);

        $jumlahSnapshot = AssessmentMatrixMappingSnapshot::query()->where('id_asesmen', $asesmen->id)->count();
        $jumlahLive = CompetencyToolMapping::query()->where('id_versi_matriks', $asesmen->id_versi_matriks)->count();

        $this->assertGreaterThan(0, $jumlahSnapshot);
        $this->assertSame($jumlahLive, $jumlahSnapshot);
    }

    public function test_perubahan_matriks_hidup_tidak_menggeser_kompetensi_wajib_asesmen(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);

        $wajibAwal = MandatoryCompetencyCoverage::idKompetensiWajib($asesmen)->sort()->values();
        $this->assertNotEmpty($wajibAwal);

        // Ubah matriks "hidup": jadikan semua pemetaan tidak wajib.
        CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->update(['wajib' => false]);

        // Asesmen memakai snapshot beku → daftar kompetensi wajib TIDAK berubah.
        $wajibSesudah = MandatoryCompetencyCoverage::idKompetensiWajib($asesmen->fresh())->sort()->values();
        $this->assertEquals($wajibAwal->all(), $wajibSesudah->all());
    }
}
