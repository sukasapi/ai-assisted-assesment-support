<?php

namespace App\Http\Resources\Api;

use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Participant
 */
class ParticipantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_peserta' => $this->kode_peserta,
            'nama_lengkap' => $this->nama_lengkap,
            'alamat_surel' => $this->alamat_surel,
            'jabatan' => $this->jabatan,
            'pendidikan' => $this->pendidikan,
            'tanggal_lahir' => $this->tanggal_lahir?->toDateString(),
        ];
    }
}
