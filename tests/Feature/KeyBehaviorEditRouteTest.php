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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeyBehaviorEditRouteTest extends TestCase
{
    use RefreshDatabase;

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
        $peserta = Participant::query()->where('kode_peserta', 'DEMO-001')->firstOrFail();
        $versi = MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('asesmen.store'), [
                'id_peserta' => $peserta->id,
                'id_versi_matriks' => $versi->id,
                'tujuan' => 'promosi',
                'tanpa_intray' => '0',
                'id_asesor' => [$admin->id],
                'metode_koleksi_bukti' => 'manual',
            ])
            ->assertRedirect();

        return Assessment::query()->latest('id')->firstOrFail();
    }
}
