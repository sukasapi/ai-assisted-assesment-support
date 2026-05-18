<?php

namespace App\Services\Ai;

use App\Jobs\AnalyzeToolPayloadAiJob;
use App\Models\AssessmentToolPayload;
use App\Models\User;
use App\Support\AiModelCatalog;

class AiAnalysisDispatcher
{
    /**
     * @return array{berhasil: bool, diantrian?: bool, pesan?: string, jumlah_perilaku_kunci?: int}
     */
    public function analisisPayloadBulk(AssessmentToolPayload $payload, User $pengguna, ?string $namaModel = null): array
    {
        $model = AiModelCatalog::selesaikan($namaModel);
        $koneksi = (string) config('ai.queue.koneksi', 'sync');

        if ($koneksi === 'sync') {
            return app(BulkToolPayloadAiAnalyzer::class)->analisisPayload($payload, $pengguna, $model);
        }

        AnalyzeToolPayloadAiJob::dispatch($payload->id, $pengguna->id, $model)
            ->onConnection($koneksi)
            ->onQueue((string) config('ai.queue.nama', 'ai-analysis'));

        $label = collect(AiModelCatalog::daftarModel())
            ->firstWhere('id', $model)['label'] ?? AiModelCatalog::labelDariId($model);

        return [
            'berhasil' => true,
            'diantrian' => true,
            'pesan' => 'Analisis AI bulk dijadwalkan (model: '.$label.'). Segarkan halaman dalam beberapa saat.',
        ];
    }

    public static function antrianAsyncAktif(): bool
    {
        return (string) config('ai.queue.koneksi', 'sync') !== 'sync';
    }
}
