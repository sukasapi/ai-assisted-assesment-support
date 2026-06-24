<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class SessionAggregateReportTest extends TestCase
{
    public function test_ringkasan_sesi_tampil_di_halaman(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $sesi = $asesmen->session;

        $this->actingAs($admin)
            ->get(route('sesi-asesmen.show', $sesi))
            ->assertOk()
            ->assertSee('Ringkasan sesi')
            ->assertSee('peringkat Job Fit', false);
    }

    public function test_laporan_sesi_pdf_dapat_diunduh(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $sesi = $asesmen->session;

        $response = $this->actingAs($admin)->get(route('sesi-asesmen.laporan-pdf', $sesi));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_konsultan_tidak_dapat_akses_laporan_sesi(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();

        $this->actingAs($konsultan)
            ->get(route('sesi-asesmen.laporan-pdf', $asesmen->session))
            ->assertForbidden();
    }
}
