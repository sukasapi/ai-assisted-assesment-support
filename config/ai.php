<?php

$kunciApi = trim((string) (env('OPENROUTER_API_KEY') ?: env('AI_OPENROUTER_API_KEY')));
$aiAktifEnv = env('AI_AKTIF');

if ($aiAktifEnv !== null && $aiAktifEnv !== '') {
    $aiAktif = filter_var($aiAktifEnv, FILTER_VALIDATE_BOOLEAN);
} else {
    /** Tanpa AI_AKTIF eksplisit: aktif otomatis jika kunci API terisi (agar tombol AI tampil di UI). */
    $aiAktif = $kunciApi !== '';
}

return [

    /*
    |--------------------------------------------------------------------------
    | Fitur AI (OpenRouter)
    |--------------------------------------------------------------------------
    |
    | Matikan dengan AI_AKTIF=false. Jika AI_AKTIF tidak di-set, fitur aktif bila
    | OPENROUTER_API_KEY atau AI_OPENROUTER_API_KEY terisi.
    |
    */

    'aktif' => $aiAktif,

    /*
    | Sinkron nama .env: OPENROUTER_* (dokumentasi umum) dan AI_OPENROUTER_* (proyek ini).
    | Untuk tiap opsi, nilai non-kosong dari OPENROUTER_* dipakai lebih dulu.
    */
    'openrouter' => [
        'kunci_api' => $kunciApi !== '' ? $kunciApi : null,
        'url_dasar' => rtrim(
            (env('OPENROUTER_BASE_URL') ?: env('AI_OPENROUTER_BASE_URL')) ?: 'https://openrouter.ai/api/v1',
            '/'
        ),
        'nama_model' => env('OPENROUTER_MODEL')
            ?: env('AI_OPENROUTER_MODEL', 'google/gemini-2.0-flash-001'),
        'batas_waktu_detik' => (int) (env('OPENROUTER_TIMEOUT') ?: env('AI_OPENROUTER_TIMEOUT', 90)),
        'suhu' => (float) (env('OPENROUTER_TEMPERATURE') ?: env('AI_OPENROUTER_TEMPERATURE', 0.2)),
        /** null|string|int — kosong / unlimited / none / -1 = tanpa max_tokens di request */
        'maks_token_keluaran' => env('AI_OPENROUTER_MAX_TOKENS', env('OPENROUTER_MAX_TOKENS')),
        'retry_total' => (int) env('OPENROUTER_RETRY_TOTAL', env('AI_OPENROUTER_RETRY_TOTAL', 2)),
        'retry_tunda_ms' => (int) env('OPENROUTER_RETRY_DELAY_MS', env('AI_OPENROUTER_RETRY_DELAY_MS', 400)),
    ],

    'queue' => [
        'koneksi' => env('AI_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'nama' => env('AI_QUEUE_NAME', 'ai-analysis'),
        'coba_maks' => (int) env('AI_QUEUE_TRIES', 3),
        'batas_waktu_detik' => (int) env('AI_QUEUE_TIMEOUT', 120),
        'jeda_detik' => (int) env('AI_QUEUE_BACKOFF', 10),
    ],

    'throttle' => [
        'request_per_menit' => (int) env('AI_TRIGGER_PER_MINUTE', 20),
        'job_per_menit' => (int) env('AI_JOB_PER_MINUTE', 30),
    ],

];
