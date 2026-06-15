<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Support\AssessmentTestHelpers;
use Tests\TestCase;

class AssessmentAccessTest extends TestCase
{

    public function test_guests_are_redirected_from_assessment_index(): void
    {
        $this->get(route('asesmen.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_assessment_index(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('asesmen.index'))
            ->assertOk();
    }

    public function test_admin_can_view_master_data_hub(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('master.index'))
            ->assertOk();
    }

    public function test_admin_creating_assessment_writes_activity_log(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('alamat_surel', 'admin@example.com')->firstOrFail();

        $asesmen = AssessmentTestHelpers::buatAsesmen($this, $admin);

        $this->assertDatabaseHas('ais_log_aktivitas', [
            'id_pengguna' => $admin->id,
            'aksi' => 'asesmen.dibuat',
            'subjek_tipe' => Assessment::class,
            'subjek_id' => $asesmen->id,
        ]);

        $log = ActivityLog::query()->where('aksi', 'asesmen.dibuat')->where('subjek_id', $asesmen->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('promosi', $log->properti['tujuan'] ?? null);
    }
}
