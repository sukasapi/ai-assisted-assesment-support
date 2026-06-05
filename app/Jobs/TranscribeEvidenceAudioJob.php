<?php

namespace App\Jobs;

use App\Enums\EvidenceTranscriptionStatus;
use App\Models\Evidence;
use App\Services\Stt\EvidenceAudioStorage;
use App\Services\Stt\EvidenceTranscriptionDispatcher;
use App\Support\EvidenceTextNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class TranscribeEvidenceAudioJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $timeout;

    /** @var array<int, int> */
    public array $backoff;

    public function __construct(
        private readonly int $idEvidence,
    ) {
        $this->tries = max(1, (int) config('stt.queue.coba_maks', 3));
        $this->timeout = max(60, (int) config('stt.queue.batas_waktu_detik', 300));
        $this->backoff = [max(1, (int) config('stt.queue.jeda_detik', 15))];
    }

    public function handle(EvidenceTranscriptionDispatcher $dispatcher, EvidenceAudioStorage $storage): void
    {
        $bukti = Evidence::query()->find($this->idEvidence);
        if ($bukti === null) {
            return;
        }

        $pathAbsolut = $storage->pathAbsolut($bukti->path_audio);
        if ($pathAbsolut === null || ! is_file($pathAbsolut)) {
            $this->tandaiGagal($bukti, 'Berkas audio tidak ditemukan.');

            return;
        }

        $bukti->forceFill([
            'status_transkripsi' => EvidenceTranscriptionStatus::Memproses,
            'pesan_status_transkripsi' => null,
        ])->save();

        try {
            $teks = $dispatcher->transcribePathLangsung($pathAbsolut);

            $normalized = EvidenceTextNormalizer::normalize(null, $teks);
            $bukti->resetAiFields();
            $bukti->forceFill([
                'teks_mentah' => $normalized !== '' ? $normalized : $teks,
                'teks_mentah_normalized' => $normalized !== '' ? $normalized : $teks,
                'status_transkripsi' => EvidenceTranscriptionStatus::Selesai,
                'pesan_status_transkripsi' => null,
            ])->save();
        } catch (Throwable $e) {
            Log::warning('Transkripsi bukti gagal', [
                'id_bukti' => $bukti->id,
                'pesan' => $e->getMessage(),
            ]);
            $this->tandaiGagal($bukti, $e->getMessage());
        }
    }

    private function tandaiGagal(Evidence $bukti, string $pesan): void
    {
        $bukti->forceFill([
            'status_transkripsi' => EvidenceTranscriptionStatus::Gagal,
            'pesan_status_transkripsi' => $pesan,
        ])->save();
    }
}
