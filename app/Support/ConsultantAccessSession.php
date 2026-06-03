<?php

namespace App\Support;

use App\Models\ConsultantAssignment;
use App\Models\User;

final class ConsultantAccessSession
{
    public const SESSION_KEY = 'penugasan_konsultan_id';

    public static function idAktif(): ?int
    {
        $id = session(self::SESSION_KEY);

        return is_numeric($id) ? (int) $id : null;
    }

    public static function penugasanAktif(?User $user = null): ?ConsultantAssignment
    {
        $user ??= auth()->user();
        $id = self::idAktif();
        if ($id === null || $user === null) {
            return null;
        }

        $row = ConsultantAssignment::query()
            ->with(['assessments', 'session'])
            ->whereKey($id)
            ->where('id_pengguna', $user->id)
            ->first();

        if ($row === null || ! $row->masihBerlaku()) {
            self::clear();

            return null;
        }

        return $row;
    }

    public static function set(int $penugasanId): void
    {
        session([self::SESSION_KEY => $penugasanId]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
