<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentTool;
use App\Models\Competency;
use App\Models\KeyBehavior;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class KeyBehaviorExportTest extends TestCase
{

    public function test_unduh_csv_mapping_kosong_mengembalikan_404(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $this->actingAs($admin)
            ->get(route('asesmen.perilaku.export-csv', ['asesmen' => $asesmen, 'delimiter' => ',']))
            ->assertNotFound();
    }

    public function test_unduh_csv_mapping_dengan_delimiter_koma(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Contoh perilaku kunci',
            'alasan_pemilihan' => 'Alasan uji',
            'kutipan_referensi' => 'Kutipan uji',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('asesmen.perilaku.export-csv', ['asesmen' => $asesmen, 'delimiter' => ',']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename=mapping-perilaku-kunci-asm-', $response->headers->get('content-disposition') ?? '');
        $this->assertStringContainsString('comma.csv', $response->headers->get('content-disposition') ?? '');

        $body = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $this->assertStringContainsString('alat_kode,kompetensi_kode', $body);
        $this->assertStringContainsString('BEI', $body);
        $this->assertStringContainsString('Contoh perilaku kunci', $body);
        $this->assertStringContainsString('Alasan uji', $body);
        $this->assertStringContainsString('Kutipan uji', $body);
    }

    public function test_unduh_csv_mapping_dengan_delimiter_titik_koma(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Perilaku titik koma',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('asesmen.perilaku.export-csv', ['asesmen' => $asesmen, 'delimiter' => ';']));

        $response->assertOk();
        $this->assertStringContainsString('semicolon.csv', $response->headers->get('content-disposition') ?? '');

        $body = $response->streamedContent();
        $this->assertStringContainsString('alat_kode;kompetensi_kode', $body);
        $this->assertStringContainsString('Perilaku titik koma', $body);
    }

    public function test_halaman_asesmen_menampilkan_tombol_unduh_jika_ada_mapping(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);
        $alat = AssessmentTool::query()->where('kode', 'BEI')->firstOrFail();
        $kompetensi = Competency::query()->orderBy('id')->firstOrFail();

        KeyBehavior::query()->create([
            'id_asesmen' => $asesmen->id,
            'id_alat_penilaian' => $alat->id,
            'id_kompetensi' => $kompetensi->id,
            'teks_perilaku' => 'Ada mapping',
        ]);

        $this->actingAs($admin)
            ->get(route('asesmen.show', $asesmen))
            ->assertOk()
            ->assertSee('Unduh Data')
            ->assertSee('modal-unduh-mapping-pk', false);
    }

    public function test_halaman_asesmen_tidak_menampilkan_tombol_unduh_jika_belum_ada_mapping(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = $this->buatAsesmen($admin);

        $this->actingAs($admin)
            ->get(route('asesmen.show', $asesmen))
            ->assertOk()
            ->assertDontSee('data-open-modal="modal-unduh-mapping-pk"', false);
    }

    private function buatAsesmen(User $admin): Assessment
    {
        return AssessmentTestHelpers::buatAsesmen($this, $admin);
    }
}
