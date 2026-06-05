<?php

namespace App\Enums;

enum EvidenceSourceType: string
{
    case Teks = 'teks';
    case Wawancara = 'wawancara';

    public function label(): string
    {
        return match ($this) {
            self::Teks => 'Bukti teks',
            self::Wawancara => 'Bukti wawancara',
        };
    }
}
