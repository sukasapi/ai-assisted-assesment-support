<?php

namespace App\Services\Stt;

use RuntimeException;
use Symfony\Component\Process\Process;

class AudioTranscriptionChunker
{
    /** @var list<string> */
    private array $pathSementara = [];

    /**
     * @return list<string> path absolut — satu atau beberapa chunk siap transkripsi
     */
    public function pecahJikaPerlu(string $pathAbsolut): array
    {
        if (! is_file($pathAbsolut)) {
            throw new RuntimeException('Berkas audio tidak ditemukan.');
        }

        $maksBytes = max(1, (int) config('stt.chunk.maks_mb_per_kirim', 24)) * 1024 * 1024;
        if (filesize($pathAbsolut) <= $maksBytes) {
            return [$pathAbsolut];
        }

        if (! config('stt.chunk.aktif', true)) {
            throw new RuntimeException(
                'Berkas audio melebihi batas unggah API ('.round($maksBytes / 1024 / 1024, 1).' MB). Kecilkan file atau aktifkan chunking STT.'
            );
        }

        return $this->pecahDenganFfmpeg($pathAbsolut);
    }

    /**
     * @param  list<string>  $paths
     */
    public function hapusChunkSementara(array $paths, string $pathAsli): void
    {
        foreach ($paths as $path) {
            if ($path === $pathAsli) {
                continue;
            }
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->pathSementara = [];
    }

    public function ffmpegTersedia(): bool
    {
        $binary = $this->pathFfmpeg();

        try {
            $proses = new Process([$binary, '-version']);
            $proses->setTimeout(10);
            $proses->run();

            return $proses->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    private function pecahDenganFfmpeg(string $pathAbsolut): array
    {
        if (! $this->ffmpegTersedia()) {
            throw new RuntimeException(
                'Berkas audio terlalu besar. Pasang FFmpeg di PATH atau set STT_FFMPEG_PATH agar sistem dapat memecah audio otomatis.'
            );
        }

        $durasiDetik = max(60, (int) config('stt.chunk.durasi_detik', 600));
        $overlapDetik = max(0, (int) config('stt.chunk.overlap_detik', 10));
        $direktori = dirname($pathAbsolut);
        $prefix = $direktori.'/stt-chunk-'.uniqid('', true);
        $polaKeluaran = $prefix.'_%03d.mp3';

        $proses = new Process([
            $this->pathFfmpeg(),
            '-hide_banner',
            '-loglevel', 'error',
            '-y',
            '-i', $pathAbsolut,
            '-ac', '1',
            '-ar', '16000',
            '-b:a', '64k',
            '-f', 'segment',
            '-segment_time', (string) $durasiDetik,
            '-segment_overlap', (string) $overlapDetik,
            '-reset_timestamps', '1',
            $polaKeluaran,
        ]);
        $proses->setTimeout(max(120, (int) config('stt.queue.batas_waktu_detik', 300)));
        $proses->run();

        if (! $proses->isSuccessful()) {
            throw new RuntimeException('Gagal memecah berkas audio: '.trim($proses->getErrorOutput()));
        }

        $chunks = glob($prefix.'_*.mp3') ?: [];
        sort($chunks, SORT_NATURAL);

        if ($chunks === []) {
            throw new RuntimeException('Pemecahan audio tidak menghasilkan chunk.');
        }

        $this->pathSementara = array_values(array_filter($chunks, fn (string $p): bool => $p !== $pathAbsolut));

        return $chunks;
    }

    private function pathFfmpeg(): string
    {
        $custom = trim((string) config('stt.chunk.ffmpeg_path', ''));

        return $custom !== '' ? $custom : 'ffmpeg';
    }
}
