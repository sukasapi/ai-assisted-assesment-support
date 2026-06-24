<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Strategi agregasi antar PK satu alat (L-3)
    |--------------------------------------------------------------------------
    | Default untuk asesmen baru bila kolom strategi_agregasi_alat belum diisi.
    | Nilai: 'max' (ambil tertinggi) atau 'rata_rata' (rerata dibulatkan).
    */
    'strategi_agregasi_alat_default' => env('INTEGRASI_STRATEGI_AGREGASI', 'max'),

    /*
    |--------------------------------------------------------------------------
    | Ambang keyakinan rendah (E-5)
    |--------------------------------------------------------------------------
    | Usulan AI dengan keyakinan < ambang ini ditandai "perlu ditinjau" dan
    | diurutkan lebih dulu agar asesor memprioritaskan review.
    */
    'ambang_keyakinan_rendah' => (float) env('INTEGRASI_AMBANG_KEYAKINAN_RENDAH', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Impor peserta
    |--------------------------------------------------------------------------
    */
    'impor_maks_baris' => (int) env('PESERTA_IMPOR_MAKS_BARIS', 10000),

];
