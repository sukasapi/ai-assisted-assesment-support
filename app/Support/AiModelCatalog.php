<?php

namespace App\Support;

use App\Models\AiOpenRouterModel;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar model OpenRouter yang boleh dipilih pengguna.
 */
final class AiModelCatalog
{
    /**
     * @return list<array{id: string, label: string}>
     */
    public static function parseDariEnv(string $raw, string $modelUtama): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $modelUtama !== '' ? [['id' => $modelUtama, 'label' => $modelUtama]] : [];
        }

        $hasil = [];
        $pemisah = (str_contains($raw, '|') || str_contains($raw, "\n") || str_contains($raw, ';'))
            ? preg_split('/\s*[|;\n]+\s*/', $raw) ?: []
            : explode(',', $raw);

        foreach ($pemisah as $bagian) {
            $bagian = trim($bagian);
            if ($bagian === '') {
                continue;
            }
            if (str_contains($bagian, ':')) {
                [$id, $label] = array_pad(explode(':', $bagian, 2), 2, '');
            } else {
                $id = $bagian;
                $label = '';
            }
            $id = trim($id);
            if ($id === '') {
                continue;
            }
            $label = trim($label);
            $hasil[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : $id,
            ];
        }

        if ($hasil === [] && $modelUtama !== '') {
            return [['id' => $modelUtama, 'label' => $modelUtama]];
        }

        return $hasil;
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public static function daftarModel(): array
    {
        $dariDb = self::daftarModelDariDatabase();
        if ($dariDb !== []) {
            return $dariDb;
        }

        $daftar = config('ai.openrouter.daftar_model');
        if (! is_array($daftar) || $daftar === []) {
            $id = (string) config('ai.openrouter.nama_model', 'google/gemini-2.0-flash-001');

            return [['id' => $id, 'label' => self::labelDariId($id)]];
        }

        $hasil = [];
        foreach ($daftar as $baris) {
            if (! is_array($baris)) {
                continue;
            }
            $id = trim((string) ($baris['id'] ?? ''));
            if ($id === '') {
                continue;
            }
            $label = trim((string) ($baris['label'] ?? ''));
            $hasil[] = [
                'id' => $id,
                'label' => $label !== '' ? $label : self::labelDariId($id),
            ];
        }

        return $hasil !== [] ? $hasil : [['id' => 'google/gemini-2.0-flash-001', 'label' => 'Gemini 2 Flash']];
    }

    public static function modelDefault(): string
    {
        $utamaDb = self::modelUtamaDariDatabase();
        if ($utamaDb !== null && self::modelDiizinkan($utamaDb)) {
            return $utamaDb;
        }

        $utama = trim((string) config('ai.openrouter.nama_model', ''));
        if ($utama !== '' && self::modelDiizinkan($utama)) {
            return $utama;
        }

        return self::daftarModel()[0]['id'];
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    private static function daftarModelDariDatabase(): array
    {
        if (! Schema::hasTable('ais_model_ai')) {
            return [];
        }

        $baris = AiOpenRouterModel::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->orderBy('label')
            ->get(['id_model_openrouter', 'label']);

        if ($baris->isEmpty()) {
            return [];
        }

        return $baris->map(static fn (AiOpenRouterModel $m): array => [
            'id' => $m->id_model_openrouter,
            'label' => $m->label !== '' ? $m->label : self::labelDariId($m->id_model_openrouter),
        ])->all();
    }

    private static function modelUtamaDariDatabase(): ?string
    {
        if (! Schema::hasTable('ais_model_ai')) {
            return null;
        }

        $utama = AiOpenRouterModel::query()
            ->where('aktif', true)
            ->where('utama', true)
            ->orderBy('urutan')
            ->value('id_model_openrouter');

        if (is_string($utama) && trim($utama) !== '') {
            return trim($utama);
        }

        $pertama = AiOpenRouterModel::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->value('id_model_openrouter');

        return is_string($pertama) && trim($pertama) !== '' ? trim($pertama) : null;
    }

    public static function modelDiizinkan(string $model): bool
    {
        $model = trim($model);
        foreach (self::daftarModel() as $baris) {
            if ($baris['id'] === $model) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kembalikan ID model yang valid; fallback ke default jika tidak dikenali.
     */
    public static function selesaikan(?string $model): string
    {
        $model = trim((string) $model);
        if ($model !== '' && self::modelDiizinkan($model)) {
            return $model;
        }

        return self::modelDefault();
    }

    public static function labelDariId(string $id): string
    {
        $potong = explode('/', $id);
        $nama = end($potong) ?: $id;

        return str_replace(['-', '_'], ' ', $nama);
    }

    /**
     * Model gratis OpenRouter (suffix :free) — dipakai bila AI_OPENROUTER_MODELS kosong.
     *
     * @return list<array{id: string, label: string}>
     */
    public static function modelGratisBawaan(): array
    {
        return [
            ['id' => 'google/gemini-2.0-flash-exp:free', 'label' => 'Gemini 2 Flash (gratis)'],
            ['id' => 'meta-llama/llama-3.2-3b-instruct:free', 'label' => 'Llama 3.2 3B (gratis)'],
            ['id' => 'qwen/qwen-2.5-7b-instruct:free', 'label' => 'Qwen 2.5 7B (gratis)'],
            ['id' => 'microsoft/phi-3-mini-128k-instruct:free', 'label' => 'Phi-3 Mini (gratis)'],
            ['id' => 'mistralai/mistral-7b-instruct:free', 'label' => 'Mistral 7B (gratis)'],
        ];
    }

    /**
     * Urutan coba: model pilihan pengguna, lalu model lain (bergantian saat timeout/gagal).
     *
     * @return list<string>
     */
    public static function rantaiFallback(?string $modelPilihan): array
    {
        $utama = self::selesaikan($modelPilihan);
        if (! config('ai.openrouter.fallback_otomatis', true)) {
            return [$utama];
        }

        $rantai = [$utama];
        foreach (array_column(self::daftarModel(), 'id') as $id) {
            if ($id !== $utama) {
                $rantai[] = $id;
            }
        }

        return array_values(array_unique($rantai));
    }
}
