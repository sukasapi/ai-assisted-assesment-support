<?php

namespace App\Jobs;

use App\Models\Evidence;
use App\Models\User;
use App\Services\Ai\EvidenceAiAnalyzer;
use App\Support\CatatAktivitas;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use RuntimeException;

class AnalyzeEvidenceAiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $timeout;

    /**
     * @var array<int, int>
     */
    public array $backoff;

    public function __construct(
        private readonly int $idEvidence,
        private readonly int $idPenggunaPemicu,
    ) {
        $this->tries = max(1, (int) config('ai.queue.coba_maks', 3));
        $this->timeout = max(30, (int) config('ai.queue.batas_waktu_detik', 120));
        $this->backoff = [max(1, (int) config('ai.queue.jeda_detik', 10))];
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new RateLimited('ai-analysis-job'))->dontRelease()];
    }

    public function handle(EvidenceAiAnalyzer $analyzer): void
    {
        $bukti = Evidence::query()->find($this->idEvidence);
        if ($bukti === null) {
            return;
        }
        $pengguna = User::query()->find($this->idPenggunaPemicu);
        if ($pengguna === null) {
            return;
        }

        $hasil = $analyzer->analisisInkremental($bukti, $pengguna);
        if (! ($hasil['berhasil'] ?? false)) {
            $pesan = (string) ($hasil['pesan'] ?? 'Analisis AI incremental gagal.');
            if ($this->layakRetry($pesan)) {
                throw new RuntimeException($pesan);
            }
            $this->fail(new RuntimeException($pesan));

            return;
        }

        CatatAktivitas::catat(
            $pengguna,
            'bukti.ai_dianalisis',
            Evidence::class,
            $bukti->id,
            ['id_asesmen' => $bukti->id_asesmen, 'sumber' => 'queue']
        );
    }

    public function failed(?\Throwable $exception): void
    {
        $pengguna = User::query()->find($this->idPenggunaPemicu);
        $bukti = Evidence::query()->find($this->idEvidence);
        CatatAktivitas::catat(
            $pengguna,
            'bukti.ai_gagal',
            Evidence::class,
            $bukti?->id,
            [
                'id_asesmen' => $bukti?->id_asesmen,
                'error' => $exception?->getMessage(),
                'sumber' => 'queue',
            ]
        );
    }

    private function layakRetry(string $pesan): bool
    {
        $p = mb_strtolower($pesan);

        return str_contains($p, 'gagal menghubungi penyedia ai')
            || str_contains($p, 'http ')
            || str_contains($p, 'timeout')
            || str_contains($p, 'connection');
    }
}
