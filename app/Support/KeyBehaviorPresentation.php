<?php

namespace App\Support;

use App\Models\Assessment;
use App\Models\AssessmentToolPayload;
use App\Models\KeyBehavior;
use Illuminate\Support\Collection;

class KeyBehaviorPresentation
{
    /**
     * @return Collection<int, int>
     */
    public static function idDariAnalisisBulk(Assessment $asesmen): Collection
    {
        return $asesmen->toolPayloads
            ->flatMap(function (AssessmentToolPayload $payload): array {
                $ids = $payload->hasil_analisis_ai['perilaku_kunci_dibuat'] ?? [];

                return is_array($ids) ? $ids : [];
            })
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
    }

    public static function sumberAi(KeyBehavior $perilaku, Collection $idDariBulk): bool
    {
        if ($idDariBulk->contains((int) $perilaku->id)) {
            return true;
        }

        $bukti = $perilaku->evidence;
        if ($bukti === null) {
            return false;
        }

        return $bukti->ai_dinilai_pada !== null || $bukti->ai_tingkat !== null;
    }

    /**
     * @return array{label: string, kelas: string}
     */
    public static function badgeSumber(KeyBehavior $perilaku, Collection $idDariBulk): array
    {
        if (self::sumberAi($perilaku, $idDariBulk)) {
            return [
                'label' => 'AI',
                'kelas' => 'bg-primary-fixed/50 text-primary',
            ];
        }

        return [
            'label' => 'Manual',
            'kelas' => 'bg-surface-container text-on-surface-variant',
        ];
    }

    /**
     * @return array{label: string, kelas: string}
     */
    public static function badgeStatus(KeyBehavior $perilaku): array
    {
        if ($perilaku->tervalidasi) {
            return [
                'label' => 'Disimpan',
                'kelas' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            ];
        }

        return [
            'label' => 'Draft',
            'kelas' => 'border-amber-200 bg-amber-50 text-amber-800',
        ];
    }
}
