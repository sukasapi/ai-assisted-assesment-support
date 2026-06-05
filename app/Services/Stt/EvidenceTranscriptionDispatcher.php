<?php

namespace App\Services\Stt;

use App\Enums\EvidenceTranscriptionStatus;
use App\Jobs\TranscribeEvidenceAudioJob;
use App\Models\Evidence;
use App\Support\EvidenceTextNormalizer;
use RuntimeException;

class EvidenceTranscriptionDispatcher
{
    public function transcribePathLangsung(string $pathAbsolut): string
    {
        if (! is_file($pathAbsolut)) {
            throw new RuntimeException('Berkas audio tidak ditemukan.');
        }

        $chunker = app(AudioTranscriptionChunker::class);
        $transcriber = app(TranscriberContract::class);
        $paths = $chunker->pecahJikaPerlu($pathAbsolut);
        $bagian = [];

        try {
            foreach ($paths as $path) {
                $format = EvidenceAudioStorage::formatDariPath($path);
                $hasil = $transcriber->transcribe($path, $format);
                $teks = trim((string) ($hasil['text'] ?? ''));
                if ($teks !== '') {
                    $bagian[] = $teks;
                }
            }
        } finally {
            $chunker->hapusChunkSementara($paths, $pathAbsolut);
        }

        if ($bagian === []) {
            throw new RuntimeException('Transkripsi menghasilkan teks kosong.');
        }

        $teks = implode("\n\n", $bagian);
        $normalized = EvidenceTextNormalizer::normalize(null, $teks);

        return $normalized !== '' ? $normalized : $teks;
    }

    public function jadwalkan(Evidence $bukti): void
    {
        $bukti->forceFill([
            'status_transkripsi' => EvidenceTranscriptionStatus::Menunggu,
            'pesan_status_transkripsi' => null,
        ])->save();

        $koneksi = (string) config('stt.queue.koneksi', 'sync');

        if ($koneksi === 'sync') {
            $bukti->forceFill(['status_transkripsi' => EvidenceTranscriptionStatus::Memproses])->save();
            TranscribeEvidenceAudioJob::dispatchSync($bukti->id);

            return;
        }

        TranscribeEvidenceAudioJob::dispatch($bukti->id)
            ->onConnection($koneksi)
            ->onQueue((string) config('stt.queue.nama', 'stt-transcription'));
    }

    public static function antrianAsyncAktif(): bool
    {
        return (string) config('stt.queue.koneksi', 'sync') !== 'sync';
    }
}
