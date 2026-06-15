<?php

namespace App\Support;

use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AssessmentShowRedirect
{
    private const VALID_TABS = ['overview', 'konfigurasi', 'pengumpulan', 'hasil-mapping'];

    public static function to(
        Assessment $asesmen,
        ?string $tab = null,
        ?int $kompetensiId = null,
        ?int $alatId = null,
        ?int $buktiId = null,
    ): RedirectResponse {
        $tab = self::normalize($tab);

        $routeParams = ['asesmen' => $asesmen];
        if ($kompetensiId !== null && $kompetensiId > 0) {
            $routeParams['kompetensi'] = $kompetensiId;
        }
        if ($alatId !== null && $alatId > 0) {
            $routeParams['alat'] = $alatId;
        }
        if ($buktiId !== null && $buktiId > 0) {
            $routeParams['bukti'] = $buktiId;
        }

        $response = redirect()->route('asesmen.show', $routeParams);

        if ($tab !== null && $tab !== 'overview') {
            $response->withFragment($tab);
        }

        return $response;
    }

    public static function fromRequest(
        Assessment $asesmen,
        Request $request,
        ?string $fallbackTab = null,
        ?int $kompetensiId = null,
        ?int $alatId = null,
        ?int $buktiId = null,
    ): RedirectResponse {
        $tab = self::normalize($request->input('asesmen_tab') ?: $fallbackTab);

        $kompetensiId ??= self::positiveInt($request->input('id_kompetensi'))
            ?? self::positiveInt($request->input('kompetensi'));
        $alatId ??= self::positiveInt($request->input('id_alat_penilaian'))
            ?? self::positiveInt($request->input('alat'));
        $buktiId ??= self::positiveInt($request->input('bukti'));

        return self::to($asesmen, $tab, $kompetensiId, $alatId, $buktiId);
    }

    private static function positiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    public static function normalize(?string $tab): ?string
    {
        if ($tab === null || $tab === '') {
            return null;
        }

        $aliases = [
            'mapping' => 'hasil-mapping',
            'ai' => 'konfigurasi',
            'evidence' => 'pengumpulan',
        ];

        $tab = $aliases[$tab] ?? $tab;

        return in_array($tab, self::VALID_TABS, true) ? $tab : null;
    }
}
