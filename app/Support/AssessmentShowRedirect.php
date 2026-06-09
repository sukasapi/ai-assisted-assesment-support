<?php

namespace App\Support;

use App\Models\Assessment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AssessmentShowRedirect
{
    private const VALID_TABS = ['overview', 'konfigurasi', 'pengumpulan', 'hasil-mapping'];

    public static function to(Assessment $asesmen, ?string $tab = null): RedirectResponse
    {
        $tab = self::normalize($tab);
        $response = redirect()->route('asesmen.show', $asesmen);

        if ($tab !== null && $tab !== 'overview') {
            $response->withFragment($tab);
        }

        return $response;
    }

    public static function fromRequest(Assessment $asesmen, Request $request, ?string $fallbackTab = null): RedirectResponse
    {
        $tab = self::normalize($request->input('asesmen_tab') ?: $fallbackTab);

        return self::to($asesmen, $tab);
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
