<?php

namespace App\Http\Requests\Master;

use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Participant $peserta */
        $peserta = $this->route('peserta');

        return [
            'kode_peserta' => ['required', 'string', 'max:255', Rule::unique('ais_peserta', 'kode_peserta')->ignore($peserta->id)],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'alamat_surel' => ['nullable', 'string', 'email', 'max:255'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'pendidikan' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'catatan' => ['nullable', 'string'],
            'aktif' => ['sometimes', 'boolean'],
            'id_versi_matriks' => ['nullable', 'integer', Rule::exists('ais_versi_matriks', 'id')->whereNull('dihapus_pada')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'aktif' => $this->boolean('aktif'),
            'id_versi_matriks' => $this->filled('id_versi_matriks') ? $this->integer('id_versi_matriks') : null,
        ]);
    }
}
