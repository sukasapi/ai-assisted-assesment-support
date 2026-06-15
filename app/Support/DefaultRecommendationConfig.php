<?php

namespace App\Support;

final class DefaultRecommendationConfig
{
    /**
     * Konfigurasi default revisi #1 (selaras pola LPP pada tingkat_tercapai 1–6).
     *
     * @return array<string, mixed>
     */
    public static function bawaan(): array
    {
        return [
            'versi_skema' => 1,
            'dimensi' => [
                [
                    'kode' => 'kompetensi_perilaku',
                    'label' => 'Kompetensi Perilaku',
                    'filter_kelompok_kode' => ['INT', 'MNJ', 'LDR'],
                    'kriteria_qualified' => [
                        'logika' => 'and',
                        'aturan' => [
                            ['jenis' => 'forbid_tingkat', 'tingkat' => 1],
                            ['jenis' => 'max_count_tingkat', 'tingkat' => 2, 'maksimum' => 3],
                        ],
                    ],
                ],
                [
                    'kode' => 'kualifikasi_professional',
                    'label' => 'Kualifikasi Professional',
                    'filter_kelompok_kode' => ['KUAL'],
                    'kriteria_qualified' => [
                        'logika' => 'and',
                        'aturan' => [
                            ['jenis' => 'min_tingkat_semua', 'tingkat' => 2],
                        ],
                    ],
                ],
            ],
            'hasil_agregat' => [
                'logika' => 'and',
                'label_qualified' => 'Memenuhi Persyaratan (Qualified)',
                'label_not_qualified' => 'Belum Memenuhi Persyaratan (Not Qualified)',
            ],
        ];
    }
}
