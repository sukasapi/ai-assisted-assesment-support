<?php

namespace App\Services\Consultant;

use App\Models\ConsultantAssignment;

final class ConsultantAccessTokenGenerator
{
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $token = self::randomToken();
            if (! ConsultantAssignment::query()->where('token_akses', $token)->exists()) {
                return $token;
            }
        }

        throw new \RuntimeException('Gagal menghasilkan token unik.');
    }

    private static function randomToken(): string
    {
        $chars = self::CHARSET;
        $len = strlen($chars);
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= $chars[random_int(0, $len - 1)];
        }

        return $out;
    }
}
