<?php

namespace App\Support;

use App\Enums\PayloadAnalysisStatus;
use App\Models\AssessmentToolPayload;

class PayloadAnalysisPresenter
{
    /**
     * @return array{
     *   status: string,
     *   label: string,
     *   pesan: string|null,
     *   sedang_berjalan: bool,
     *   diproses_pada: string|null,
     *   dijadwalkan_pada: string|null,
     *   jumlah_usulan: int,
     *   jumlah_perilaku_kunci: int,
     *   dapat_dihapus: bool,
     *   alasan_tidak_dihapus: string|null,
     * }
     */
    public static function ringkasan(AssessmentToolPayload $payload): array
    {
        $status = self::statusTerselesaikan($payload);
        $alasanTidakDihapus = ToolPayloadDeletionGuard::alasanTidakDapatDihapus($payload);

        return [
            'status' => $status->value,
            'label' => $status->label(),
            'pesan' => $payload->pesan_status_analisis,
            'sedang_berjalan' => $status->sedangBerjalan(),
            'diproses_pada' => $payload->diproses_pada?->toIso8601String(),
            'dijadwalkan_pada' => $payload->dijadwalkan_pada?->toIso8601String(),
            'jumlah_usulan' => count($payload->hasil_analisis_ai['usulan'] ?? []),
            'jumlah_perilaku_kunci' => count($payload->hasil_analisis_ai['perilaku_kunci_dibuat'] ?? []),
            'dapat_dihapus' => $alasanTidakDihapus === null,
            'alasan_tidak_dihapus' => $alasanTidakDihapus,
        ];
    }

    public static function statusTerselesaikan(AssessmentToolPayload $payload): PayloadAnalysisStatus
    {
        $tersimpan = $payload->status_analisis;
        if ($tersimpan instanceof PayloadAnalysisStatus) {
            return $tersimpan;
        }
        if (is_string($tersimpan) && $tersimpan !== '') {
            return PayloadAnalysisStatus::tryFrom($tersimpan) ?? PayloadAnalysisStatus::Belum;
        }

        if ($payload->diproses_pada !== null && is_array($payload->hasil_analisis_ai)) {
            return PayloadAnalysisStatus::Berhasil;
        }

        return PayloadAnalysisStatus::Belum;
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(AssessmentToolPayload $payload): array
    {
        $ringkasan = self::ringkasan($payload);
        $payload->loadMissing(['tool', 'uploader']);

        return array_merge($ringkasan, [
            'id' => $payload->id,
            'id_asesmen' => $payload->id_asesmen,
            'alat' => [
                'kode' => $payload->tool?->kode,
                'nama' => $payload->tool?->nama,
            ],
            'teks_muatan' => (string) $payload->teks_muatan,
            'panjang_teks' => mb_strlen((string) $payload->teks_muatan),
            'pengunggah' => $payload->uploader?->name,
            'dibuat_pada' => $payload->dibuat_pada?->toIso8601String(),
            'diperbarui_pada' => $payload->diperbarui_pada?->toIso8601String(),
            'usulan' => $payload->hasil_analisis_ai['usulan'] ?? [],
        ]);
    }
}
