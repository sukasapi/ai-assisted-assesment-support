<?php

namespace Tests\Feature;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\CompetencyIntegration;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class CompetencyIntegrationTest extends TestCase
{

    public function test_hitung_pratinjau_kosong_tanpa_pk_disahkan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $this->actingAs($admin)
            ->post(route('asesmen.integrasi.hitung', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHas('status');

        $this->assertSame(0, CompetencyIntegration::query()->where('id_asesmen', $asesmen->id)->count());
    }

    public function test_hitung_pratinjau_menyimpan_gap_dan_job_fit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $this->isiPkDisahkan($asesmen, $admin);

        $this->actingAs($admin)
            ->post(route('asesmen.integrasi.hitung', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHas('status');

        $asesmen->refresh();
        $this->assertSame(AssessmentStatus::Terintegrasi, $asesmen->status);
        $this->assertNotNull($asesmen->integrasi_pratinjau_pada);
        $this->assertGreaterThan(0, CompetencyIntegration::query()->where('id_asesmen', $asesmen->id)->count());
        $this->assertNotNull($asesmen->job_fit_persen_pratinjau);

        $this->assertDatabaseHas('ais_log_aktivitas', [
            'aksi' => 'asesmen.integrasi_dihitung',
            'subjek_id' => $asesmen->id,
        ]);
    }

    public function test_hitung_pratinjau_ditolak_setelah_finalisasi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $this->isiPkDisahkan($asesmen, $admin);

        $this->actingAs($admin)->patch(route('asesmen.finalisasi', $asesmen))->assertRedirect();

        $this->actingAs($admin)
            ->post(route('asesmen.integrasi.hitung', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('integrasi');
    }

    private function buatAsesmen(User $admin): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin);
    }

    private function isiPkDisahkan(Assessment $asesmen, User $admin): void
    {
        $idAlatAktif = $asesmen->toolSelections()->where('aktif', true)->pluck('id_alat_penilaian')->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where('aktif', true)
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique();

        $alat = AssessmentTool::query()->whereIn('id', $idAlatAktif)->orderBy('id')->firstOrFail();

        foreach ($idKompetensiWajib as $idKompetensi) {
            $idTingkat = CompetencyLevel::query()
                ->where('id_kompetensi', $idKompetensi)
                ->whereNull('dihapus_pada')
                ->orderBy('tingkat')
                ->value('id');

            if ($idTingkat === null) {
                continue;
            }

            $asesmen->keyBehaviors()->updateOrCreate(
                [
                    'id_alat_penilaian' => $alat->id,
                    'id_kompetensi' => (int) $idKompetensi,
                ],
                [
                    'id_tingkat_kompetensi' => (int) $idTingkat,
                    'teks_perilaku' => 'PK disahkan uji integrasi',
                    'tervalidasi' => true,
                    'id_pengguna_validasi' => $admin->id,
                    'waktu_validasi' => now(),
                ],
            );
        }
    }
}
