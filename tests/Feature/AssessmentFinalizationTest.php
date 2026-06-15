<?php

namespace Tests\Feature;

use App\Enums\AssessmentStatus;
use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class AssessmentFinalizationTest extends TestCase
{

    public function test_status_tetap_draf_selama_belum_finalisasi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $asesmen->refresh();
        $this->assertSame(AssessmentStatus::Draf, $asesmen->status);
        $this->assertNull($asesmen->waktu_finalisasi);
    }

    public function test_finalisasi_ditolak_jika_kompetensi_wajib_belum_terpenuhi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.finalisasi', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('finalisasi');

        $asesmen->refresh();
        $this->assertSame(AssessmentStatus::Draf, $asesmen->status);
        $this->assertNull($asesmen->waktu_finalisasi);
    }

    public function test_finalisasi_ditolak_jika_pk_hanya_draft(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $idAlatAktif = $asesmen->toolSelections()->where('aktif', true)->pluck('id_alat_penilaian')->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where('aktif', true)
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique()
            ->first();

        $alat = AssessmentTool::query()->whereIn('id', $idAlatAktif)->orderBy('id')->firstOrFail();
        $idTingkat = CompetencyLevel::query()
            ->where('id_kompetensi', $idKompetensiWajib)
            ->whereNull('dihapus_pada')
            ->orderBy('tingkat')
            ->value('id');

        $asesmen->keyBehaviors()->create([
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => (int) $idKompetensiWajib,
            'id_tingkat_kompetensi' => (int) $idTingkat,
            'teks_perilaku' => 'Draft belum disahkan',
            'tervalidasi' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('asesmen.finalisasi', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('finalisasi');
    }

    public function test_finalisasi_berhasil_jika_semua_kompetensi_wajib_bernilai(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $idAlatAktif = $asesmen->toolSelections()
            ->where('aktif', true)
            ->pluck('id_alat_penilaian')
            ->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where('aktif', true)
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique()
            ->values();

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

            $asesmen->keyBehaviors()->create([
                'id_alat_penilaian' => $alat->id,
                'id_kompetensi' => (int) $idKompetensi,
                'id_tingkat_kompetensi' => (int) $idTingkat,
                'teks_perilaku' => 'Terpenuhi via uji finalisasi',
                'tervalidasi' => true,
                'id_pengguna_validasi' => User::query()->where('alamat_surel', 'admin@example.com')->value('id'),
                'waktu_validasi' => now(),
            ]);
        }

        $this->actingAs($admin)
            ->patch(route('asesmen.finalisasi', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHas('status');

        $asesmen->refresh();
        $this->assertSame(AssessmentStatus::SelesaiFinal, $asesmen->status);
        $this->assertNotNull($asesmen->waktu_finalisasi);
        $this->assertDatabaseHas('ais_log_aktivitas', [
            'aksi' => 'asesmen.difinalisasi',
            'subjek_tipe' => Assessment::class,
            'subjek_id' => $asesmen->id,
        ]);
    }

    public function test_admin_bisa_membatalkan_finalisasi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $this->isiSemuaKompetensiWajib($asesmen);

        $this->actingAs($admin)->patch(route('asesmen.finalisasi', $asesmen))->assertRedirect();
        $this->actingAs($admin)
            ->patch(route('asesmen.batal-finalisasi', $asesmen))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHas('status');

        $asesmen->refresh();
        $this->assertSame(AssessmentStatus::Draf, $asesmen->status);
        $this->assertNull($asesmen->waktu_finalisasi);
        $this->assertDatabaseHas('ais_log_aktivitas', [
            'aksi' => 'asesmen.finalisasi_dibatalkan',
            'subjek_tipe' => Assessment::class,
            'subjek_id' => $asesmen->id,
        ]);
    }

    public function test_konsultan_tidak_bisa_membatalkan_finalisasi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $this->isiSemuaKompetensiWajib($asesmen);
        $this->actingAs($admin)->patch(route('asesmen.finalisasi', $asesmen))->assertRedirect();

        $token = 'FINAL123';
        AssessmentTestHelpers::buatPenugasanKonsultan($this, $konsultan, $asesmen, $admin, $token);
        AssessmentTestHelpers::masukTokenKonsultan($this, $konsultan, $token);

        $this->actingAs($konsultan)
            ->patch(route('asesmen.batal-finalisasi', $asesmen))
            ->assertForbidden();
    }

    private function buatAsesmen(User $admin): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin);
    }

    private function isiSemuaKompetensiWajib(Assessment $asesmen): void
    {
        $idAlatAktif = $asesmen->toolSelections()
            ->where('aktif', true)
            ->pluck('id_alat_penilaian')
            ->all();
        $idKompetensiWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $idAlatAktif)
            ->where('aktif', true)
            ->where('wajib', true)
            ->pluck('id_kompetensi')
            ->unique()
            ->values();

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

            $asesmen->keyBehaviors()->updateOrCreate([
                'id_alat_penilaian' => $alat->id,
                'id_kompetensi' => (int) $idKompetensi,
            ], [
                'id_tingkat_kompetensi' => (int) $idTingkat,
                'teks_perilaku' => 'Terpenuhi via helper uji finalisasi',
                'tervalidasi' => true,
                'waktu_validasi' => now(),
            ]);
        }
    }
}
