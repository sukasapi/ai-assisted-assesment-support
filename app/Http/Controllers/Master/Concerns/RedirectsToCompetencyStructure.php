<?php

namespace App\Http\Controllers\Master\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsToCompetencyStructure
{
    protected function redirectToCompetencyStructure(?string $status = null, ?string $error = null, ?string $expand = null): RedirectResponse
    {
        $params = array_filter(['expand' => $expand]);
        $response = redirect()->route('master.kompetensi.index', $params);

        if ($error !== null) {
            return $response->with('error', $error);
        }

        return $response->with('status', $status ?? 'Perubahan disimpan.');
    }
}
