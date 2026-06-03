<?php

namespace Tests\Support;

use App\Models\Assessment;
use App\Models\AssessmentSession;
use App\Models\ConsultantAssignment;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Testing\TestResponse;

final class AssessmentTestHelpers
{
    public static function sesiUji(): AssessmentSession
    {
        $adminId = User::query()->where('peran', 'admin')->value('id');

        return AssessmentSession::query()->firstOrCreate(
            ['kode_sesi' => 'SES-TEST'],
            [
                'nama' => 'Sesi uji',
                'status' => 'aktif',
                'id_pengguna_pembuat' => $adminId,
            ]
        );
    }

    /**
     * @param  \Illuminate\Foundation\Testing\TestCase  $test
     */
    public static function buatAsesmen(
        $test,
        User $admin,
        ?Participant $peserta = null,
        ?MatrixVersion $versi = null,
        array $extra = [],
    ): Assessment {
        $peserta ??= Participant::query()->where('kode_peserta', 'DEMO-001')->firstOrFail();
        $versi ??= MatrixVersion::query()->where('kode_versi', 'KAMUS-17-DEFAULT')->firstOrFail();
        $sesi = self::sesiUji();

        /** @var TestResponse $response */
        $response = $test->actingAs($admin)->post(route('sesi-asesmen.asesmen.store', $sesi), array_merge([
            'id_peserta' => $peserta->id,
            'id_versi_matriks' => $versi->id,
            'tujuan' => 'promosi',
            'metode_koleksi_bukti' => 'manual',
            'tanpa_intray' => '0',
            'id_asesor' => [$admin->id],
        ], $extra));

        $response->assertRedirect();

        return Assessment::query()->latest('id')->firstOrFail();
    }

    /**
     * @param  \Illuminate\Foundation\Testing\TestCase  $test
     */
    public static function buatPenugasanKonsultan(
        $test,
        User $konsultan,
        Assessment $asesmen,
        User $admin,
        string $token = 'TEST1234',
        ?AssessmentSession $sesi = null,
    ): ConsultantAssignment {
        $sesi ??= $asesmen->session ?? self::sesiUji();

        $penugasan = ConsultantAssignment::query()->create([
            'id_pengguna' => $konsultan->id,
            'id_sesi_asesmen' => $sesi->id,
            'token_akses' => $token,
            'aktif' => true,
            'id_pengguna_pembuat' => $admin->id,
        ]);

        $penugasan->assessments()->attach($asesmen->id);

        return $penugasan;
    }

    /**
     * @param  \Illuminate\Foundation\Testing\TestCase  $test
     */
    public static function masukTokenKonsultan($test, User $konsultan, string $token): void
    {
        $test->actingAs($konsultan)
            ->post(route('asesmen.token.verify'), ['token_akses' => $token])
            ->assertRedirect(route('asesmen.index'));
    }
}
