<?php

namespace App\Support;

use App\Enums\PayloadAnalysisStatus;
use App\Models\AssessmentToolPayload;
use App\Models\KeyBehavior;

class ToolPayloadDeletionGuard
{
    /**
     * @return array<int, int>
     */
    public static function idPerilakuKunciDariAnalisis(AssessmentToolPayload $payload): array
    {
        $ids = $payload->hasil_analisis_ai['perilaku_kunci_dibuat'] ?? [];

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $ids,
        ), static fn (int $id): bool => $id > 0)));
    }

    public static function alasanTidakDapatDihapus(AssessmentToolPayload $payload): ?string
    {
        $status = PayloadAnalysisPresenter::statusTerselesaikan($payload);
        if ($status === PayloadAnalysisStatus::Antrian || $status === PayloadAnalysisStatus::Memproses) {
            return 'Analisis bulk sedang berjalan. Tunggu hingga selesai sebelum menghapus payload.';
        }

        $ids = self::idPerilakuKunciDariAnalisis($payload);
        if ($ids === []) {
            return null;
        }

        $adaDisetujui = KeyBehavior::query()
            ->whereIn('id', $ids)
            ->where('id_asesmen', $payload->id_asesmen)
            ->where('tervalidasi', true)
            ->exists();

        if ($adaDisetujui) {
            return 'Payload tidak dapat dihapus karena ada mapping perilaku kunci yang sudah disetujui dari analisis bulk ini.';
        }

        return null;
    }

    public static function dapatDihapus(AssessmentToolPayload $payload): bool
    {
        return self::alasanTidakDapatDihapus($payload) === null;
    }

    /**
     * Hapus perilaku kunci draft (belum disetujui) yang dibuat dari analisis bulk payload ini.
     */
    public static function hapusPerilakuKunciDraft(AssessmentToolPayload $payload): void
    {
        $ids = self::idPerilakuKunciDariAnalisis($payload);
        if ($ids === []) {
            return;
        }

        KeyBehavior::query()
            ->whereIn('id', $ids)
            ->where('id_asesmen', $payload->id_asesmen)
            ->where('tervalidasi', false)
            ->delete();
    }
}
