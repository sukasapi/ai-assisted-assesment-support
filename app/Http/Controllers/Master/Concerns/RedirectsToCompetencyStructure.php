<?php

namespace App\Http\Controllers\Master\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsToCompetencyStructure
{
    protected function redirectToCompetencyStructure(?string $status = null, ?string $error = null): RedirectResponse
    {
        $response = redirect()->route('master.kompetensi.index');

        if ($error !== null) {
            return $response->with('error', $error);
        }

        return $response->with('status', $status ?? 'Perubahan disimpan.');
    }
}
