<?php

namespace App\Services\Ai;

use Illuminate\Http\Client\Response;

/**
 * Kontrak klien chat AI (OpenRouter / Google Gemini / penyedia lain yang kompatibel
 * dengan skema /chat/completions OpenAI). Pemilihan penyedia diatur lewat
 * config('ai.penyedia') dan di-bind di AppServiceProvider.
 */
interface ChatClientContract
{
    /**
     * Coba model secara bergantian (pilihan pengguna dulu, lalu model lain) bila timeout/error server.
     *
     * @param  list<array{role: string, content: string}>  $pesan
     * @return array{response: Response, latency_ms: int, nama_model: string, dicoba_model: list<string>}
     */
    public function chatCompletionDenganFallback(
        array $pesan,
        ?string $namaModel = null,
        int|string|null $maksTokenKeluaran = null,
    ): array;

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
    ): array;
}
