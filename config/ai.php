<?php

$kunciApi = trim((string) (env('OPENROUTER_API_KEY') ?: env('AI_OPENROUTER_API_KEY')));
$aiAktifEnv = env('AI_AKTIF');

if ($aiAktifEnv !== null && $aiAktifEnv !== '') {
    $aiAktif = filter_var($aiAktifEnv, FILTER_VALIDATE_BOOLEAN);
} else {
    /** Tanpa AI_AKTIF eksplisit: aktif otomatis jika kunci API terisi (agar tombol AI tampil di UI). */
    $aiAktif = $kunciApi !== '';
}

$modelUtama = (string) (env('OPENROUTER_MODEL') ?: env('AI_OPENROUTER_MODEL', 'google/gemini-2.0-flash-exp:free'));
$rawDaftarModel = env('AI_OPENROUTER_MODELS', env('OPENROUTER_MODELS'));
$rawString = is_string($rawDaftarModel) ? trim($rawDaftarModel) : '';
if ($rawString === '') {
    $daftarModel = \App\Support\AiModelCatalog::modelGratisBawaan();
} else {
    $daftarModel = \App\Support\AiModelCatalog::parseDariEnv($rawString, $modelUtama);
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
        'nama_model' => $modelUtama,
        /** @var list<array{id: string, label: string}> */
        'daftar_model' => $daftarModel,
        /** @deprecated gunakan batas_waktu_per_model_detik */
        'batas_waktu_detik' => (int) (env('OPENROUTER_TIMEOUT') ?: env('AI_OPENROUTER_TIMEOUT', 90)),
        /** Batas tunggu respons per model (detik); habis → coba model berikutnya */
        'batas_waktu_per_model_detik' => (int) (env('AI_OPENROUTER_TIMEOUT_PER_MODEL', env('OPENROUTER_TIMEOUT_PER_MODEL', 45))),
        /** Batas koneksi TCP ke OpenRouter (detik) */
        'batas_waktu_koneksi_detik' => (int) (env('AI_OPENROUTER_CONNECT_TIMEOUT', env('OPENROUTER_CONNECT_TIMEOUT', 12))),
        /** true = otomatis coba model lain di daftar bila timeout / error server */
        'fallback_otomatis' => filter_var(
            env('AI_OPENROUTER_FALLBACK', env('OPENROUTER_FALLBACK', true)),
            FILTER_VALIDATE_BOOLEAN
        ),
        'suhu' => (float) (env('OPENROUTER_TEMPERATURE') ?: env('AI_OPENROUTER_TEMPERATURE', 0.2)),
        /** null|string|int — kosong / unlimited / none / -1 = tanpa max_tokens di request */
        'maks_token_keluaran' => env('AI_OPENROUTER_MAX_TOKENS', env('OPENROUTER_MAX_TOKENS')),
        /** Batas token khusus analisis bulk (angka positif); mempercepat respons model */
        'maks_token_keluaran_bulk' => env('AI_OPENROUTER_MAX_TOKENS_BULK', env('OPENROUTER_MAX_TOKENS_BULK', 8192)),
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
