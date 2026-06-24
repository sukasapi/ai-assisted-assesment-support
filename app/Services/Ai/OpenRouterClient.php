<?php

namespace App\Services\Ai;

use App\Support\AiModelCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterClient implements ChatClientContract
{
    /**
     * Coba model secara bergantian (pilihan pengguna dulu, lalu model lain di daftar) bila timeout/error server.
     *
     * @param  list<array{role: string, content: string}>  $pesan
     * @return array{response: Response, latency_ms: int, nama_model: string, dicoba_model: list<string>}
     */
    public function chatCompletionDenganFallback(
        array $pesan,
        ?string $namaModel = null,
        int|string|null $maksTokenKeluaran = null,
    ): array {
        $rantai = AiModelCatalog::rantaiFallback($namaModel);
        $dicoba = [];
        $kesalahan = [];

        foreach ($rantai as $model) {
            $dicoba[] = $model;
            try {
                $hasil = $this->chatCompletion($pesan, $model, $maksTokenKeluaran);
                $response = $hasil['response'];

                if ($response->successful()) {
                    return [
                        'response' => $response,
                        'latency_ms' => $hasil['latency_ms'],
                        'nama_model' => $model,
                        'dicoba_model' => $dicoba,
                    ];
                }

                $pesanHttp = 'HTTP '.$response->status();
                $kesalahan[] = $model.': '.$pesanHttp;

                if (! $this->layakCobaModelBerikutnyaUntukHttp($response->status())) {
                    break;
                }
            } catch (\Throwable $e) {
                $kesalahan[] = $model.': '.$e->getMessage();

                if (! $this->layakCobaModelBerikutnyaUntukException($e)) {
                    throw $e;
                }
            }
        }

        throw new RuntimeException(
            'Semua model AI gagal atau melebihi batas waktu ('
            .(int) config('ai.openrouter.batas_waktu_per_model_detik', 45).' dtk/model): '
            .implode(' | ', $kesalahan)
        );
    }

    /**
     * Satu model (tanpa fallback).
     *
     * @param  list<array{role: string, content: string}>  $pesan
     * @return array{response: Response, latency_ms: int}
     */
    public function chatCompletion(
        array $pesan,
        ?string $namaModel = null,
        int|string|null $maksTokenKeluaran = null,
    ): array {
        $url = config('ai.openrouter.url_dasar').'/chat/completions';
        $kunci = config('ai.openrouter.kunci_api');
        if (! is_string($kunci) || $kunci === '') {
            throw new RuntimeException('Kunci API OpenRouter tidak diatur.');
        }

        $mulai = (int) (microtime(true) * 1000);

        $badan = [
            'model' => $namaModel ?: (string) config('ai.openrouter.nama_model'),
            'messages' => $pesan,
            'temperature' => (float) config('ai.openrouter.suhu', 0.2),
            'response_format' => ['type' => 'json_object'],
        ];

        $sumberToken = $maksTokenKeluaran ?? config('ai.openrouter.maks_token_keluaran');
        $maksToken = $this->parseMaksTokenKeluaran($sumberToken);
        if ($maksToken !== null) {
            $badan['max_tokens'] = $maksToken;
        }

        $timeout = max(5, (int) config('ai.openrouter.batas_waktu_per_model_detik', 45));
        $connectTimeout = max(3, (int) config('ai.openrouter.batas_waktu_koneksi_detik', 12));
        $retry = (bool) config('ai.openrouter.fallback_otomatis', true)
            ? 0
            : max(0, (int) config('ai.openrouter.retry_total', 2));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$kunci,
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ])
            ->retry($retry, max(0, (int) config('ai.openrouter.retry_tunda_ms', 400)))
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->acceptJson()
            ->post($url, $badan);

        $latency = (int) (microtime(true) * 1000) - $mulai;

        return ['response' => $response, 'latency_ms' => $latency];
    }

    private function layakCobaModelBerikutnyaUntukHttp(int $status): bool
    {
        if (! config('ai.openrouter.fallback_otomatis', true)) {
            return false;
        }

        return in_array($status, [408, 429, 500, 502, 503, 504], true);
    }

    private function layakCobaModelBerikutnyaUntukException(\Throwable $e): bool
    {
        if (! config('ai.openrouter.fallback_otomatis', true)) {
            return false;
        }

        if ($e instanceof ConnectionException) {
            return true;
        }

        $pesan = mb_strtolower($e->getMessage());

        return str_contains($pesan, 'timed out')
            || str_contains($pesan, 'timeout')
            || str_contains($pesan, 'connection')
            || str_contains($pesan, 'could not resolve')
            || str_contains($pesan, 'ssl');
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

        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return '';
        }

        $bagian = [];
        foreach ($content as $potong) {
            if (is_string($potong)) {
                $bagian[] = $potong;

                continue;
            }
            if (is_array($potong)) {
                $teks = $potong['text'] ?? $potong['content'] ?? null;
                if (is_string($teks) && $teks !== '') {
                    $bagian[] = $teks;
                }
            }
        }

        return implode("\n", $bagian);
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
