<?php

namespace App\Services\Stt;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EvidenceAudioStorage
{
    public function simpanUpload(UploadedFile $berkas, int $idAsesmen): array
    {
        $ekstensi = strtolower($berkas->getClientOriginalExtension() ?: $berkas->extension() ?: 'bin');
        $nama = Str::uuid()->toString().'.'.$ekstensi;
        $direktori = 'evidence-audio/'.$idAsesmen;
        $path = $berkas->storeAs($direktori, $nama, 'local');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Gagal menyimpan berkas audio.');
        }

        return [
            'path' => $path,
            'mime' => $berkas->getMimeType() ?: 'application/octet-stream',
        ];
    }

    public function hapus(?string $path): void
    {
        if (! is_string($path) || $path === '') {
            return;
        }

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function pathAbsolut(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        return Storage::disk('local')->path($path);
    }

    public static function formatDariPath(string $path): string
    {
        $ekstensi = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ekstensi) {
            'mp3' => 'mp3',
            'wav' => 'wav',
            'm4a', 'mp4' => 'm4a',
            'webm' => 'webm',
            'ogg' => 'ogg',
            'flac' => 'flac',
            'aac' => 'aac',
            default => $ekstensi !== '' ? $ekstensi : 'wav',
        };
    }
}
