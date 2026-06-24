<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class DashboardRoleTest extends TestCase
{
    public function test_dasbor_admin_menampilkan_ringkasan_global(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        AssessmentTestHelpers::buatAsesmen($this, $admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Total asesmen')
            ->assertSee('Siap ditinjau / difinalisasi', false);
    }

    public function test_dasbor_konsultan_menampilkan_asesmen_saya(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();
        $konsultan = User::query()->where('alamat_surel', 'konsultan@example.com')->firstOrFail();
        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);
        AssessmentTestHelpers::buatPenugasanKonsultan($this, $konsultan, $asesmen, $admin);

        $this->actingAs($konsultan)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Asesmen saya')
            ->assertSee('Perlu tindakan', false)
            ->assertSee($asesmen->participant->nama_lengkap);
    }
}
