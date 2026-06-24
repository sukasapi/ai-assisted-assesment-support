<?php

namespace App\Services\Ai;

use App\Support\AiModelCatalog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien Google Gemini (Google AI Studio) lewat endpoint kompatibel-OpenAI:
 * https://generativelanguage.googleapis.com/v1beta/openai/chat/completions
 *
 * Bentuk request/response identik dengan OpenAI/OpenRouter (messages, choices,
 * usage), sehingga parser respons (OpenRouterClient::ekstrakIsiJson / metadataUsage)
 * dapat dipakai bersama. Perbedaan: base URL Google, tanpa header HTTP-Referer/X-Title,
 * dan nama model tanpa awalan vendor (mis. "gemini-2.0-flash", bukan "google/...").
 */
class GeminiChatClient implements ChatClientContract
{
    /**
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
            $model = $this->normalkanNamaModel($model);
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

                $kesalahan[] = $model.': HTTP '.$response->status();

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
            .(int) config('ai.gemini.batas_waktu_per_model_detik', 45).' dtk/model): '
            .implode(' | ', $kesalahan)
        );
    }

    /**
     * @param  list<array{role: string, content: string}>  $pesan
     * @return array{response: Response, latency_ms: int}
     */
    public function chatCompletion(
        array $pesan,
        ?string $namaModel = null,
        int|string|null $maksTokenKeluaran = null,
    ): array {
        $url = config('ai.gemini.url_dasar').'/chat/completions';
        $kunci = config('ai.gemini.kunci_api');
        if (! is_string($kunci) || $kunci === '') {
            throw new RuntimeException('Kunci API Gemini (GEMINI_API_KEY) tidak diatur.');
        }

        $mulai = (int) (microtime(true) * 1000);

        $badan = [
            'model' => $this->normalkanNamaModel($namaModel ?: (string) config('ai.gemini.nama_model')),
            'messages' => $pesan,
            'temperature' => (float) config('ai.gemini.suhu', 0.2),
            'response_format' => ['type' => 'json_object'],
        ];

        $sumberToken = $maksTokenKeluaran ?? config('ai.gemini.maks_token_keluaran');
        $maksToken = $this->parseMaksTokenKeluaran($sumberToken);
        if ($maksToken !== null) {
            $badan['max_tokens'] = $maksToken;
        }

        $timeout = max(5, (int) config('ai.gemini.batas_waktu_per_model_detik', 45));
        $connectTimeout = max(3, (int) config('ai.gemini.batas_waktu_koneksi_detik', 12));
        $retry = (bool) config('ai.gemini.fallback_otomatis', true)
            ? 0
            : max(0, (int) config('ai.gemini.retry_total', 2));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$kunci,
        ])
            ->retry($retry, max(0, (int) config('ai.gemini.retry_tunda_ms', 400)))
            ->connectTimeout($connectTimeout)
            ->timeout($timeout)
            ->acceptJson()
            ->post($url, $badan);

        $latency = (int) (microtime(true) * 1000) - $mulai;

        return ['response' => $response, 'latency_ms' => $latency];
    }

    /**
     * Buang awalan vendor ("google/") dan suffix gratis (":free") agar diterima Google API.
     */
    private function normalkanNamaModel(string $model): string
    {
        $model = trim($model);
        if (str_contains($model, '/')) {
            $potong = explode('/', $model);
            $model = (string) end($potong);
        }
        if (str_contains($model, ':')) {
            $model = explode(':', $model, 2)[0];
        }

        return $model;
    }

    private function layakCobaModelBerikutnyaUntukHttp(int $status): bool
    {
        if (! config('ai.gemini.fallback_otomatis', true)) {
            return false;
        }

        return in_array($status, [408, 429, 500, 502, 503, 504], true);
    }

    private function layakCobaModelBerikutnyaUntukException(\Throwable $e): bool
    {
        if (! config('ai.gemini.fallback_otomatis', true)) {
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
}
