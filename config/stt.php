<?php

$kunciOpenRouter = trim((string) (env('OPENROUTER_API_KEY') ?: env('AI_OPENROUTER_API_KEY')));
$kunciGroq = trim((string) env('GROQ_API_KEY'));
$penyedia = strtolower(trim((string) env('STT_PROVIDER', 'groq')));
$sttAktifEnv = env('STT_AKTIF');

if ($sttAktifEnv !== null && $sttAktifEnv !== '') {
    $sttAktif = filter_var($sttAktifEnv, FILTER_VALIDATE_BOOLEAN);
} else {
    $sttAktif = match ($penyedia) {
        'openrouter' => $kunciOpenRouter !== '',
        default => $kunciGroq !== '' || $kunciOpenRouter !== '',
    };
}

if ($penyedia === 'openrouter' && $kunciOpenRouter === '') {
    $penyedia = $kunciGroq !== '' ? 'groq' : 'openrouter';
}

if ($penyedia === 'groq' && $kunciGroq === '' && $kunciOpenRouter !== '') {
    $penyedia = 'openrouter';
}

return [
    'aktif' => $sttAktif,

    /** groq (gratis tier) | openrouter (berbayar) */
    'penyedia' => in_array($penyedia, ['groq', 'openrouter'], true) ? $penyedia : 'groq',

    /** Model OpenRouter — dipakai bila STT_PROVIDER=openrouter */
    'model' => (string) env('STT_MODEL', 'openai/whisper-large-v3-turbo'),

    'bahasa' => (string) env('STT_LANGUAGE', 'id'),

    /** Jeda antar segmen (detik) yang dianggap paragraf baru */
    'silence_gap_detik' => (float) env('STT_SILENCE_GAP_DETIK', 1.5),

    /** Batas unggah ke aplikasi (MB); chunking memecah sebelum kirim ke API */
    'maks_file_mb' => (int) env('STT_MAX_FILE_MB', 100),

    'mimes' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('STT_ALLOWED_MIMES', 'audio/mpeg,audio/wav,audio/x-wav,audio/mp4,audio/x-m4a,audio/webm,audio/ogg'))
    ))),

    'ekstensi' => ['mp3', 'wav', 'm4a', 'webm', 'ogg', 'flac', 'aac', 'mp4'],

    'groq' => [
        'kunci_api' => $kunciGroq !== '' ? $kunciGroq : null,
        'url_dasar' => rtrim((string) env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'), '/'),
        /** Free tier Groq — Whisper, 99+ bahasa termasuk Indonesia */
        'model' => (string) env('GROQ_STT_MODEL', 'whisper-large-v3-turbo'),
    ],

    'chunk' => [
        'aktif' => filter_var(env('STT_CHUNK_AKTIF', true), FILTER_VALIDATE_BOOLEAN),
        /** Maks ukuran per request ke API (MB) — Groq free ~25 MB */
        'maks_mb_per_kirim' => (int) env('STT_CHUNK_MAX_MB', 24),
        'durasi_detik' => (int) env('STT_CHUNK_DURASI_DETIK', 600),
        'overlap_detik' => (int) env('STT_CHUNK_OVERLAP_DETIK', 10),
        'ffmpeg_path' => (string) env('STT_FFMPEG_PATH', ''),
    ],

    'queue' => [
        'koneksi' => env('STT_QUEUE_CONNECTION', env('AI_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync'))),
        'nama' => env('STT_QUEUE_NAME', 'stt-transcription'),
        'coba_maks' => (int) env('STT_QUEUE_TRIES', env('AI_QUEUE_TRIES', 3)),
        'batas_waktu_detik' => (int) env('STT_QUEUE_TIMEOUT', 600),
        'jeda_detik' => (int) env('STT_QUEUE_BACKOFF', 15),
    ],
];
