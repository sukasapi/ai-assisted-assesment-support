<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Contracts\Auth\Authenticatable;

final class CatatAktivitas
{
    /**
     * @param  array<string, mixed>|null  $properti
     */
    public static function catat(
        ?Authenticatable $pengguna,
        string $aksi,
        ?string $subjekTipe = null,
        ?int $subjekId = null,
        ?array $properti = null,
    ): void {
        ActivityLog::query()->create([
            'id_pengguna' => $pengguna?->getAuthIdentifier(),
            'aksi' => $aksi,
            'subjek_tipe' => $subjekTipe,
            'subjek_id' => $subjekId,
            'properti' => $properti,
            'alamat_ip' => request()?->ip(),
            'dibuat_pada' => now(),
        ]);
    }
}
