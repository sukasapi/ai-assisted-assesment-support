<?php

namespace App\Enums;

enum EvidenceTranscriptionStatus: string
{
    case Menunggu = 'menunggu';
    case Memproses = 'memproses';
    case Selesai = 'selesai';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Menunggu => 'Menunggu transkripsi',
            self::Memproses => 'Memproses transkripsi',
            self::Selesai => 'Transkripsi selesai',
            self::Gagal => 'Transkripsi gagal',
        };
    }
}
