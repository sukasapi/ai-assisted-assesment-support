<?php

namespace App\Support;

/**
 * Status fitur AI untuk UI dan validasi (selaras dengan config/ai.php).
 */
final class AiFeature
{
    public static function aktif(): bool
    {
        return (bool) config('ai.aktif', false);
    }

    public static function kunciApiTerkonfigurasi(): bool
    {
        $kunci = config('ai.openrouter.kunci_api');

        return is_string($kunci) && trim($kunci) !== '';
    }

    /**
     * Tombol analisis AI boleh ditampilkan (aktif atau kunci ada — submit hanya jika aktif).
     */
    public static function tombolDapatDitampilkan(): bool
    {
        return self::aktif() || self::kunciApiTerkonfigurasi();
    }

    public static function pesanNonaktif(): string
    {
        if (self::aktif()) {
            return '';
        }

        if (! self::kunciApiTerkonfigurasi()) {
            return 'Fitur AI nonaktif: isi OPENROUTER_API_KEY atau AI_OPENROUTER_API_KEY di .env, lalu jalankan php artisan config:clear.';
        }

        return 'Fitur AI dimatikan (AI_AKTIF=false). Ubah .env atau jalankan php artisan config:clear setelah memperbarui konfigurasi.';
    }
}
