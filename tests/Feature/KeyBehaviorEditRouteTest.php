<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\KeyBehavior;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class KeyBehaviorEditRouteTest extends TestCase
{

    public function test_halaman_ubah_perilaku_kunci_dapat_diakses(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Contoh perilaku kunci',
        ]);

        $this->actingAs($admin)
            ->get(route('asesmen.perilaku.edit', [$asesmen, $pk]))
            ->assertOk()
            ->assertSee('Ubah perilaku kunci');
    }

    public function test_update_tidak_mengubah_kutipan_dan_teks_perilaku_dari_input(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Teks resmi asli',
            'kutipan_referensi' => 'Kutipan verbatim dari payload',
            'alasan_pemilihan' => 'Alasan awal',
        ]);

        $this->actingAs($admin)
            ->patch(route('asesmen.perilaku.update', [$asesmen, $pk]), [
                'alasan_pemilihan' => 'Alasan diperbarui',
                'teks_perilaku' => 'Teks diserang via form',
                'kutipan_referensi' => 'Kutipan diserang via form',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen));

        $pk->refresh();
        $this->assertSame('Alasan diperbarui', $pk->alasan_pemilihan);
        $this->assertSame('Teks resmi asli', $pk->teks_perilaku);
        $this->assertSame('Kutipan verbatim dari payload', $pk->kutipan_referensi);
    }

    public function test_sahkan_dari_tabel_tanpa_halaman_edit(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();
        $tingkat = $kompetensi->levels()->whereNull('dihapus_pada')->orderBy('tingkat')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'id_tingkat_kompetensi' => $tingkat->id,
            'teks_perilaku' => 'Draft dari AI',
            'tervalidasi' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('asesmen.perilaku.sahkan', [$asesmen, $pk]))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHas('status');

        $pk->refresh();
        $this->assertTrue($pk->tervalidasi);
    }

    public function test_sahkan_mengembalikan_json_untuk_permintaan_ajax(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();
        $tingkat = $kompetensi->levels()->whereNull('dihapus_pada')->orderBy('tingkat')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'id_tingkat_kompetensi' => $tingkat->id,
            'teks_perilaku' => 'Draft dari AI',
            'tervalidasi' => false,
        ]);

        $response = $this->actingAs($admin)
            ->patchJson(route('asesmen.perilaku.sahkan', [$asesmen, $pk]));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'status_label' => 'Disimpan',
            ])
            ->assertJsonStructure(['status_kelas', 'message']);

        $pk->refresh();
        $this->assertTrue($pk->tervalidasi);
    }

    public function test_simpan_sebagai_mapping_mengatur_tervalidasi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();
        $tingkat = $kompetensi->levels()->whereNull('dihapus_pada')->orderBy('tingkat')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'id_tingkat_kompetensi' => $tingkat->id,
            'teks_perilaku' => 'Draft',
            'tervalidasi' => false,
        ]);

        $this->actingAs($admin)
            ->patch(route('asesmen.perilaku.update', [$asesmen, $pk]), [
                'simpan_sebagai_mapping' => '1',
                'alasan_pemilihan' => 'Disetujui asesor',
            ])
            ->assertRedirect(route('asesmen.show', $asesmen));

        $pk->refresh();
        $this->assertTrue($pk->tervalidasi);
        $this->assertSame($admin->id, $pk->id_pengguna_validasi);
    }

    public function test_perilaku_kunci_bukan_milik_asesmen_mengembalikan_404(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmenA = $this->buatAsesmen($admin);
        $asesmenB = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmenB->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Milik asesmen lain',
        ]);

        $this->actingAs($admin)
            ->get(route('asesmen.perilaku.edit', [$asesmenA, $pk]))
            ->assertNotFound();
    }

    private function buatAsesmen(User $admin): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin);
    }
}
