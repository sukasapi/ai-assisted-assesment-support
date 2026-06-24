<?php

namespace App\Http\Resources\Api;

use App\Models\KeyBehavior;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KeyBehavior
 */
class KeyBehaviorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'alat' => $this->tool?->kode,
            'kompetensi' => $this->competency?->kode_kompetensi,
            'tingkat' => $this->competencyLevel?->tingkat,
            'teks_perilaku' => $this->teks_perilaku,
            'alasan_pemilihan' => $this->alasan_pemilihan,
            'kutipan_referensi' => $this->kutipan_referensi,
            'keyakinan' => $this->keyakinan !== null ? (float) $this->keyakinan : null,
            'tervalidasi' => (bool) $this->tervalidasi,
        ];
    }
}
