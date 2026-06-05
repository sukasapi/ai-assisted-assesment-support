<?php

namespace App\Services\Stt;

use App\Support\TranscriptParagraphFormatter;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterSttTranscriber implements TranscriberContract
{
    /**
     * @return array{text: string, durasi_detik: ?float, segments: list<array{start: float, end: float, text: string}>}
     */
    public function transcribe(string $pathFileAbsolut, string $formatAudio): array
    {
        if (! is_file($pathFileAbsolut)) {
            throw new RuntimeException('Berkas audio tidak ditemukan.');
        }

        if (! config('stt.aktif', false)) {
            throw new RuntimeException('Fitur transkripsi audio tidak aktif.');
        }

        $kunci = config('ai.openrouter.kunci_api');
        if (! is_string($kunci) || $kunci === '') {
            throw new RuntimeException('Kunci API OpenRouter tidak diatur.');
        }

        $isi = file_get_contents($pathFileAbsolut);
        if ($isi === false) {
            throw new RuntimeException('Gagal membaca berkas audio.');
        }

        $badan = [
            'model' => (string) config('stt.model', 'openai/whisper-large-v3-turbo'),
            'input_audio' => [
                'data' => base64_encode($isi),
                'format' => $formatAudio,
            ],
        ];

        $bahasa = trim((string) config('stt.bahasa', ''));
        if ($bahasa !== '') {
            $badan['language'] = $bahasa;
        }

        $timeout = max(30, (int) config('stt.queue.batas_waktu_detik', 300));
        $connectTimeout = max(5, (int) config('ai.openrouter.batas_waktu_koneksi_detik', 12));
        $url = rtrim((string) config('ai.openrouter.url_dasar'), '/').'/audio/transcriptions';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$kunci,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->acceptJson()
            ->post($url, $badan);

        if (! $response->successful()) {
            $pesan = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Transkripsi gagal: '.(is_string($pesan) ? $pesan : 'HTTP '.$response->status()));
        }

        $data = $response->json();
        $teksMentah = trim((string) ($data['text'] ?? ''));
        $segments = self::normalisasiSegmen($data);
        $durasi = isset($data['usage']['seconds']) ? (float) $data['usage']['seconds'] : null;

        $gap = (float) config('stt.silence_gap_detik', 1.5);
        $teks = $segments !== []
            ? TranscriptParagraphFormatter::dariSegmen($segments, $gap)
            : TranscriptParagraphFormatter::dariTeksUtuh($teksMentah);

        if ($teks === '' && $teksMentah !== '') {
            $teks = $teksMentah;
        }

        return [
            'text' => $teks,
            'durasi_detik' => $durasi,
            'segments' => $segments,
        ];
    }

    /**
     * @return list<array{start: float, end: float, text: string}>
     */
    private static function normalisasiSegmen(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $candidates = $data['segments'] ?? $data['transcript']['segments'] ?? null;
        if (! is_array($candidates)) {
            return [];
        }

        $hasil = [];
        foreach ($candidates as $seg) {
            if (! is_array($seg)) {
                continue;
            }
            $teks = trim((string) ($seg['text'] ?? ''));
            if ($teks === '') {
                continue;
            }
            $hasil[] = [
                'start' => (float) ($seg['start'] ?? 0),
                'end' => (float) ($seg['end'] ?? 0),
                'text' => $teks,
            ];
        }

        return $hasil;
    }
}
