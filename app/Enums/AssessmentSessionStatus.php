<?php

namespace App\Enums;

enum AssessmentSessionStatus: string
{
    case Draf = 'draf';
    case Aktif = 'aktif';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Aktif => 'Aktif',
            self::Selesai => 'Selesai',
        };
    }
}
