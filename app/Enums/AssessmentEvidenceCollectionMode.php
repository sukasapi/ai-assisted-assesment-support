<?php

namespace App\Enums;

enum AssessmentEvidenceCollectionMode: string
{
    case Manual = 'manual';
    case PayloadAlat = 'payload_alat';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual (bukti per kompetensi)',
            self::PayloadAlat => 'Otomatis (payload alat + AI bulk)',
        };
    }

    public function deskripsiSingkat(): string
    {
        return match ($this) {
            self::Manual => 'Tambahkan bukti penilaian per alat dan kompetensi; analisis AI per baris bukti.',
            self::PayloadAlat => 'Unggah teks muatan per alat lalu jalankan pemetaan AI bulk; review usulan sebelum dipakai resmi.',
        };
    }
}
