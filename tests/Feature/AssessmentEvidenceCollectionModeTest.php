<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\CompetencyToolMapping;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentEvidenceCollectionModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_metode_payload_mencegah_tambah_bukti_manual(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'payload_alat');

        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $alat->id,
                'id_kompetensi' => $kompetensi->id,
                'teks_mentah' => 'Teks uji',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('metode_koleksi_bukti');

        $this->assertDatabaseMissing('ais_bukti_penilaian', [
            'id_asesmen' => $asesmen->id,
            'teks_mentah' => 'Teks uji',
        ]);
    }

    public function test_metode_manual_mencegah_unggah_payload(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'manual');

        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.payload-alat.store', $asesmen), [
                'id_alat_penilaian' => $alat->id,
                'teks_muatan' => 'Dump panjang',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('metode_koleksi_bukti');

        $this->assertDatabaseMissing('ais_payload_alat_asesmen', [
            'id_asesmen' => $asesmen->id,
        ]);
    }

    public function test_patch_metode_mencatat_perubahan(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'manual');

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->patch(route('asesmen.metode-koleksi-bukti.update', $asesmen), [
                'metode_koleksi_bukti' => 'payload_alat',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen));

        $asesmen->refresh();
        $this->assertSame('payload_alat', $asesmen->metode_koleksi_bukti->value);

        $this->assertDatabaseHas('ais_log_aktivitas', [
            'id_pengguna' => $admin->id,
            'aksi' => 'asesmen.metode_koleksi_bukti.diubah',
            'subjek_tipe' => Assessment::class,
            'subjek_id' => $asesmen->id,
        ]);
    }

    public function test_bukti_manual_ditolak_jika_alat_tidak_dipakai_matriks(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'manual');

        $alatAktif = $asesmen->toolSelections()->where('aktif', true)->orderBy('id')->firstOrFail();
        CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->where('id_alat_penilaian', $alatAktif->id_alat_penilaian)
            ->update(['aktif' => false]);

        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.bukti.store', $asesmen), [
                'id_alat_penilaian' => $alatAktif->id_alat_penilaian,
                'id_kompetensi' => $kompetensi->id,
                'teks_mentah' => 'Teks uji alat non matriks',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('id_alat_penilaian');
    }

    public function test_payload_ditolak_jika_alat_tidak_dipakai_matriks(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'payload_alat');

        $alatAktif = $asesmen->toolSelections()->where('aktif', true)->orderBy('id')->firstOrFail();
        CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->where('id_alat_penilaian', $alatAktif->id_alat_penilaian)
            ->update(['aktif' => false]);

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.payload-alat.store', $asesmen), [
                'id_alat_penilaian' => $alatAktif->id_alat_penilaian,
                'teks_muatan' => 'Payload uji alat non matriks',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('id_alat_penilaian');
    }

    private function buatAsesmen(User $admin, string $metode): Assessment
    {
        $peserta = Participant::query()->where('kode_peserta', 'DEMO-001')->firstOrFail();
        $versi = MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('asesmen.store'), [
                'id_peserta' => $peserta->id,
                'id_versi_matriks' => $versi->id,
                'tujuan' => 'promosi',
                'tanpa_intray' => '0',
                'id_asesor' => [$admin->id],
                'metode_koleksi_bukti' => $metode,
            ])
            ->assertRedirect();

        return Assessment::query()->latest('id')->firstOrFail();
    }
}
