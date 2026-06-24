<?php

namespace App\Enums;

/**
 * Cara menggabungkan beberapa perilaku kunci dari SATU alat menjadi satu tingkat
 * sebelum dirata-rata-tertimbang antar alat (L-3).
 */
enum AssessmentToolAggregationStrategy: string
{
    case Maksimum = 'max';
    case RataRata = 'rata_rata';

    public function label(): string
    {
        return match ($this) {
            self::Maksimum => 'Maksimum (ambil tingkat tertinggi)',
            self::RataRata => 'Rata-rata (rerata semua tingkat)',
        };
    }

    public function deskripsiSingkat(): string
    {
        return match ($this) {
            self::Maksimum => 'Bila satu alat punya beberapa PK, dipakai tingkat TERTINGGI. Cocok bila satu bukti kuat sudah memadai.',
            self::RataRata => 'Bila satu alat punya beberapa PK, dipakai RERATA tingkat (dibulatkan). Cocok bila konsistensi antar bukti penting.',
        };
    }
}
