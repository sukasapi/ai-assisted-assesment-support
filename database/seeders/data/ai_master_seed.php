<?php

/**
 * Data awal master AI: template prompt & model OpenRouter.
 */
return [
    'template_prompt' => [
        [
            'kode' => 'STAR',
            'nama' => 'STAR (Situation, Task, Action, Result)',
            'deskripsi' => 'Kerangka wawancara perilaku untuk BEI dan alat naratif lainnya.',
            'urutan' => 10,
            'alat_kode' => ['BEI'],
            'teks_instruksi' => <<<'TXT'
Gunakan kerangka STAR untuk menafsirkan bukti naratif (wawancara, cerita kejadian, log BEI, dll.):
- Situation (S): konteks, latar, dan pemicu kejadian.
- Task (T): tugas, tanggung jawab, atau target yang diemban subjek.
- Action (A): tindakan konkret yang dilakukan subjek (bukan tim secara umum kecuali teks menyebut peran subjek).
- Result (R): hasil/outcome terukur atau dampak yang terjadi.

Aturan analisis:
1) Periksa apakah teks mentah memuat unsur STAR (meski tidak berlabel eksplisit).
2) Dalam field "alasan" dan "konfirmatori", sebutkan bagian STAR yang teridentifikasi dari kutipan verbatim.
3) Jika satu atau lebih unsur STAR tidak dapat ditemukan di teks, nyatakan secara eksplisit unsur yang kurang; pertimbangkan menurunkan "keyakinan" bila struktur jawaban tidak lengkap, tanpa mengarang unsur yang hilang.
4) Tetap patuhi aturan kutipan verbatim; jangan menambahkan fakta di luar teks.
5) Penilaian tingkat kompetensi tetap mengacu indikator perilaku kamus, bukan hanya kelengkapan STAR.
TXT,
        ],
        [
            'kode' => 'UMUM',
            'nama' => 'Umum (tanpa kerangka khusus)',
            'deskripsi' => 'Analisis standar tanpa instruksi kerangka tambahan.',
            'urutan' => 99,
            'teks_instruksi' => '',
        ],
    ],
];
