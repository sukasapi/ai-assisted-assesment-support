<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentToolDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_tool_diagnostic_json(): void
    {
        $this->seed(DatabaseSeeder::class);

        $asesmen = Assessment::query()->firstOrFail();

        $this->getJson(route('asesmen.diagnostik-alat', $asesmen))
            ->assertUnauthorized();
    }

    public function test_admin_receives_tool_diagnostic_json(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = Assessment::query()->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson(route('asesmen.diagnostik-alat', $asesmen))
            ->assertOk()
            ->assertJsonStructure([
                'id_asesmen',
                'id_versi_matriks',
                'kode_versi_matriks',
                'nama_versi_matriks',
                'metode_koleksi_bukti',
                'ringkasan_matriks' => ['jumlah_pemetaan', 'jumlah_alat_unik'],
                'alat_di_matriks',
                'pemilihan_preset',
                'alat_tersedia_input',
                'pemilihan_tanpa_pemetaan',
                'punya_alat_tersedia',
            ]);

        $response->assertJsonPath('id_asesmen', $asesmen->id);
        $response->assertJsonPath('kode_versi_matriks', 'KAMUS-17-DEFAULT');
        $this->assertTrue($response->json('punya_alat_tersedia'));
        $this->assertNotEmpty($response->json('alat_tersedia_input'));

        $kodeTersedia = collect($response->json('alat_tersedia_input'))->pluck('kode')->all();
        $this->assertContains('BEI', $kodeTersedia);
        $this->assertContains('PA', $kodeTersedia);
    }

    public function test_new_assessment_diagnostic_lists_tools_without_matrix_mapping(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $peserta = Participant::query()->where('kode_peserta', 'DEMO-001')->firstOrFail();
        $versi = MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('asesmen.store'), [
                'id_peserta' => $peserta->id,
                'id_versi_matriks' => $versi->id,
                'tujuan' => 'promosi',
                'tanpa_intray' => '0',
                'metode_koleksi_bukti' => 'manual',
                'id_asesor' => [$admin->id],
            ])
            ->assertRedirect();

        $asesmen = Assessment::query()->latest('id')->firstOrFail();

        $response = $this->actingAs($admin)
            ->getJson(route('asesmen.diagnostik-alat', $asesmen))
            ->assertOk();

        $tanpaPemetaan = collect($response->json('pemilihan_tanpa_pemetaan'))->pluck('kode')->all();
        $this->assertContains('INTRAY', $tanpaPemetaan);
        $this->assertContains('LGD', $tanpaPemetaan);
    }
}
