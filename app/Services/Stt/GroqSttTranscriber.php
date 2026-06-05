<?php

namespace App\Services\Stt;

use App\Support\TranscriptParagraphFormatter;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqSttTranscriber implements TranscriberContract
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

        $kunci = config('stt.groq.kunci_api');
        if (! is_string($kunci) || $kunci === '') {
            throw new RuntimeException('Kunci API Groq tidak diatur (GROQ_API_KEY).');
        }

        $timeout = max(30, (int) config('stt.queue.batas_waktu_detik', 300));
        $url = rtrim((string) config('stt.groq.url_dasar'), '/').'/audio/transcriptions';

        $response = Http::withToken($kunci)
            ->timeout($timeout)
            ->attach(
                'file',
                file_get_contents($pathFileAbsolut) ?: '',
                basename($pathFileAbsolut),
            )
            ->post($url, array_filter([
                'model' => (string) config('stt.groq.model', 'whisper-large-v3-turbo'),
                'language' => trim((string) config('stt.bahasa', '')) ?: null,
                'response_format' => 'verbose_json',
                'temperature' => 0,
            ]));

        if (! $response->successful()) {
            $pesan = $response->json('error.message') ?? $response->body();

            throw new RuntimeException('Transkripsi Groq gagal: '.(is_string($pesan) ? $pesan : 'HTTP '.$response->status()));
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Respons transkripsi Groq tidak valid.');
        }

        $teksMentah = trim((string) ($data['text'] ?? ''));
        $segments = self::normalisasiSegmen($data);
        $durasi = isset($data['duration']) ? (float) $data['duration'] : null;

        $gap = (float) config('stt.silence_gap_detik', 1.5);
        $teks = $segments !== []
            ? TranscriptParagraphFormatter::dariSegmen($segments, $gap)
            : TranscriptParagraphFormatter::dariTeksUtuh($teksMentah);

        if ($teks === '' && $teksMentah !== '') {
            $teks = $teksMentah;
        }

        if ($teks === '') {
            throw new RuntimeException('Transkripsi Groq menghasilkan teks kosong.');
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
    private static function normalisasiSegmen(array $data): array
    {
        $candidates = $data['segments'] ?? null;
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
