<?php

namespace App\Enums;

enum PayloadAnalysisStatus: string
{
    case Belum = 'belum';
    case Antrian = 'antrian';
    case Memproses = 'memproses';
    case Berhasil = 'berhasil';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Belum => 'Belum dianalisis',
            self::Antrian => 'Dalam antrian',
            self::Memproses => 'Sedang diproses',
            self::Berhasil => 'Berhasil',
            self::Gagal => 'Gagal',
        };
    }

    public function sedangBerjalan(): bool
    {
        return $this === self::Antrian || $this === self::Memproses;
    }
}
