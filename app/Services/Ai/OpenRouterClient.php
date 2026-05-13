<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenRouterClient
{
    /**
     * @param  list<array{role: string, content: string}>  $pesan
     * @return array{response: Response, latency_ms: int}
     */
    public function chatCompletion(array $pesan): array
    {
        $url = config('ai.openrouter.url_dasar').'/chat/completions';
        $kunci = config('ai.openrouter.kunci_api');
        if (! is_string($kunci) || $kunci === '') {
            throw new \RuntimeException('Kunci API OpenRouter tidak diatur.');
        }

        $mulai = (int) (microtime(true) * 1000);

        $badan = [
            'model' => config('ai.openrouter.nama_model'),
            'messages' => $pesan,
            'temperature' => (float) config('ai.openrouter.suhu', 0.2),
            'response_format' => ['type' => 'json_object'],
        ];

        $maksToken = $this->parseMaksTokenKeluaran(config('ai.openrouter.maks_token_keluaran'));
        if ($maksToken !== null) {
            $badan['max_tokens'] = $maksToken;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$kunci,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])
            ->retry(
                max(0, (int) config('ai.openrouter.retry_total', 2)),
                max(0, (int) config('ai.openrouter.retry_tunda_ms', 400))
            )
            ->timeout((int) config('ai.openrouter.batas_waktu_detik', 90))
            ->acceptJson()
            ->post($url, $badan);

        $latency = (int) (microtime(true) * 1000) - $mulai;

        return ['response' => $response, 'latency_ms' => $latency];
    }

    /**
     * Tanpa batas di sisi klien: jangan kirim max_tokens (biarkan default penyedia/model).
     * Angka positif = batas eksplisit.
     */
    private function parseMaksTokenKeluaran(mixed $nilai): ?int
    {
        if ($nilai === null) {
            return null;
        }
        if (is_int($nilai)) {
            return $nilai > 0 ? $nilai : null;
        }
        $s = strtolower(trim((string) $nilai));
        if ($s === '' || $s === 'unlimited' || $s === 'none' || $s === '-1' || $s === '0') {
            return null;
        }
        if (ctype_digit($s)) {
            $n = (int) $s;

            return $n > 0 ? $n : null;
        }

        return null;
    }

    public static function ekstrakIsiJson(Response $response): string
    {
        $data = $response->json();
        $choices = $data['choices'] ?? [];
        $first = $choices[0] ?? [];
        $message = $first['message'] ?? [];
        $content = $message['content'] ?? '';

        return is_string($content) ? $content : '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function metadataUsage(Response $response): ?array
    {
        $data = $response->json();
        $usage = $data['usage'] ?? null;

        return is_array($usage) ? $usage : null;
    }
}
