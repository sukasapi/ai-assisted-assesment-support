<?php

namespace Tests\Feature;

use App\Enums\PayloadAnalysisStatus;
use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class PayloadAnalysisStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_endpoint_mengembalikan_ringkasan_payload(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenPayload($admin);

        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => 'Teks contoh payload untuk uji status.',
            'status_analisis' => PayloadAnalysisStatus::Berhasil,
            'diproses_pada' => now(),
            'hasil_analisis_ai' => ['usulan' => [['kode_kompetensi' => 'ST']]],
        ]);

        $this->actingAs($admin)
            ->getJson(route('asesmen.payload-alat.statuses', $asesmen))
            ->assertOk()
            ->assertJsonPath("payloads.{$payload->id}.status", 'berhasil')
            ->assertJsonPath("payloads.{$payload->id}.label", 'Berhasil');
    }

    public function test_detail_endpoint_mengembalikan_teks_lengkap(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmenPayload($admin);

        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $teks = 'Cuplikan perilaku lengkap untuk modal detail.';
        $payload = $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $alat->id,
            'teks_muatan' => $teks,
        ]);

        $this->actingAs($admin)
            ->getJson(route('asesmen.payload-alat.show', [$asesmen, $payload]))
            ->assertOk()
            ->assertJsonPath('teks_muatan', $teks)
            ->assertJsonPath('id', $payload->id);
    }

    private function buatAsesmenPayload(User $admin): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin, extra: [
            'metode_koleksi_bukti' => 'payload_alat',
        ]);
    }
}
