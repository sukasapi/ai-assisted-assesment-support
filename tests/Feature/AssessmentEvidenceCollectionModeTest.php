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
use Tests\Support\AssessmentTestHelpers;
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
                'jenis_sumber' => 'teks',
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
                'jenis_sumber' => 'teks',
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

    public function test_get_analisis_ai_bulk_redirects_to_show_with_message(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'payload_alat');
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Teks uji GET redirect.',
            'id_pengguna_pengunggah' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('asesmen.payload-alat.analisis-ai.get', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('ai');
    }

    public function test_show_payload_mode_menampilkan_tombol_analisis_ai_bulk(): void
    {
        $this->seed(DatabaseSeeder::class);
        config(['ai.aktif' => true]);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin, 'payload_alat');
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $this->actingAs($admin)
            ->from(route('asesmen.show', $asesmen))
            ->post(route('asesmen.payload-alat.store', $asesmen), [
                'id_alat_penilaian' => $alat->id,
                'teks_muatan' => 'Teks muatan uji bulk.',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen));

        $this->actingAs($admin)
            ->get(route('asesmen.show', $asesmen))
            ->assertOk()
            ->assertSee('Analisis AI bulk', false)
            ->assertSee('data-ai-mode="bulk"', false);
    }

    private function buatAsesmen(User $admin, string $metode): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin, extra: [
            'metode_koleksi_bukti' => $metode,
        ]);
    }
}
