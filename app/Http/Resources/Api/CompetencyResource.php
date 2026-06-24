<?php

namespace App\Http\Resources\Api;

use App\Models\Competency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Competency
 */
class CompetencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode' => $this->kode_kompetensi,
            'nama' => $this->nama,
            'definisi' => $this->definisi,
            'tingkat_maksimum' => (int) ($this->tingkat_maksimum ?? 6),
            'kelompok' => $this->whenLoaded('group', fn () => [
                'kode' => $this->group?->kode,
                'nama' => $this->group?->nama,
            ]),
        ];
    }
}
