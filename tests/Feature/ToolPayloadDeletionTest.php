<?php

namespace Tests\Feature;

use App\Enums\PayloadAnalysisStatus;
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

class ToolPayloadDeletionTest extends TestCase
{

    public function test_payload_belum_dianalisis_dapat_dihapus(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenPayload($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Teks untuk dihapus.',
        ]);

        $this->actingAs($admin)
            ->delete(route('asesmen.payload-alat.destroy', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen));

        $this->assertDatabaseMissing('ais_payload_alat_asesmen', ['id' => $payload->id]);
    }

    public function test_payload_dengan_perilaku_kunci_draft_dihapus_bersama_draft(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenPayload($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Draft dari bulk',
            'tervalidasi' => false,
        ]);

        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Teks bulk.',
            'status_analisis' => PayloadAnalysisStatus::Berhasil,
            'diproses_pada' => now(),
            'hasil_analisis_ai' => [
                'usulan' => [],
                'perilaku_kunci_dibuat' => [$pk->id],
            ],
        ]);

        $this->actingAs($admin)
            ->delete(route('asesmen.payload-alat.destroy', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen));

        $this->assertDatabaseMissing('ais_payload_alat_asesmen', ['id' => $payload->id]);
        $this->assertDatabaseMissing('ais_perilaku_kunci', ['id' => $pk->id]);
    }

    public function test_payload_dengan_perilaku_kunci_disetujui_tidak_dapat_dihapus(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenPayload($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        $pk = KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Sudah disetujui',
            'tervalidasi' => true,
            'id_pengguna_validasi' => $admin->id,
            'waktu_validasi' => now(),
        ]);

        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Teks bulk.',
            'status_analisis' => PayloadAnalysisStatus::Berhasil,
            'diproses_pada' => now(),
            'hasil_analisis_ai' => [
                'usulan' => [],
                'perilaku_kunci_dibuat' => [$pk->id],
            ],
        ]);

        $this->actingAs($admin)
            ->delete(route('asesmen.payload-alat.destroy', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('payload');

        $this->assertDatabaseHas('ais_payload_alat_asesmen', ['id' => $payload->id]);
        $this->assertDatabaseHas('ais_perilaku_kunci', ['id' => $pk->id]);
    }

    public function test_payload_sedang_diproses_tidak_dapat_dihapus(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenPayload($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();

        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Teks bulk.',
            'status_analisis' => PayloadAnalysisStatus::Memproses,
        ]);

        $this->actingAs($admin)
            ->delete(route('asesmen.payload-alat.destroy', [$asesmen, $payload]))
            ->assertRedirect(route('asesmen.show', $asesmen))
            ->assertSessionHasErrors('payload');

        $this->assertDatabaseHas('ais_payload_alat_asesmen', ['id' => $payload->id]);
    }

    private function buatAsesmenPayload(User $admin): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin, extra: [
            'metode_koleksi_bukti' => 'payload_alat',
        ]);
    }
}
