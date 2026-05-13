<?php

/**
 * Data contoh untuk ContohVersiMatriksPaketSeeder.
 *
 * - Versi matriks: metadata kamus/paket penilaian.
 * - pola_pemetaan_per_kelompok: untuk setiap kompetensi dalam kelompok (kode INT/MNJ/LDR),
 *   pasangan ke alat penilaian (kode alat) beserta wajib & bobot.
 * - indikator_perilaku_contoh: teks indikator per tingkat untuk beberapa kode kompetensi
 *   (di-update pada master ais_tingkat_kompetensi; indikator tidak di-FK ke versi matriks,
 *   tetapi dipakai bersama saat menilai perilaku untuk kompetensi tersebut).
 */
return [
    'versi_matriks' => [
        'kode_versi' => 'CONTOH-PAKET-2026',
        'nama_versi' => 'Paket contoh penilaian multi-alat (seeder)',
        'kunci_kamus' => 'DEMO2026',
        'catatan_konteks' => 'Versi demonstrasi: pemetaan kompetensi–alat dibedakan per kelompok (inti / manajerial / kepemimpinan). Indikator perilaku di master kompetensi dapat disesuaikan; entri di bawah hanya memperkaya contoh teks untuk beberapa kompetensi.',
        'aktif' => true,
        'bawaan' => false,
        'dipublikasikan_pada' => null,
    ],

    'pola_pemetaan_per_kelompok' => [
        'INT' => [
            ['alat' => 'BEI', 'wajib' => true, 'bobot' => 1],
            ['alat' => 'PA', 'wajib' => false, 'bobot' => 0.5],
        ],
        'MNJ' => [
            ['alat' => 'BEI', 'wajib' => true, 'bobot' => 1],
            ['alat' => 'LGD', 'wajib' => false, 'bobot' => 1],
            ['alat' => 'CASE', 'wajib' => false, 'bobot' => 0.75],
        ],
        'LDR' => [
            ['alat' => 'BEI', 'wajib' => true, 'bobot' => 1],
            ['alat' => 'RP', 'wajib' => false, 'bobot' => 1],
            ['alat' => 'INT', 'wajib' => false, 'bobot' => 0.5],
        ],
    ],

    'indikator_perilaku_contoh' => [
        'ACH' => [
            1 => '[Contoh seeder] Menetapkan target kinerja pribadi yang realistis dan terukur untuk pekerjaan rutin.',
            2 => '[Contoh seeder] Secara konsisten berupaya melampaui standar yang disepakati dan memonitor capaian.',
        ],
        'TRL' => [
            1 => '[Contoh seeder] Mengkomunikasikan arah perubahan kepada tim dengan bahasa yang jelas dan relevan.',
        ],
        'BCR' => [
            3 => '[Contoh seeder] Membangun jejaring kerja lintas unit untuk menyelesaikan isu bersama.',
        ],
    ],
];
