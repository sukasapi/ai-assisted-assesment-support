<?php

namespace App\Http\Requests;

use App\Models\AssessmentSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultantAssignmentRequest extends FormRequest
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
        $sesi = $this->route('sesiAsesmen');
        $idSesi = $sesi instanceof AssessmentSession ? $sesi->id : null;

        return [
            'id_pengguna' => [
                'required',
                'integer',
                Rule::exists('ais_pengguna', 'id')->where('peran', 'konsultan')->where('aktif', true),
            ],
            'id_asesmen' => ['required', 'array', 'min:1'],
            'id_asesmen.*' => [
                'integer',
                Rule::exists('ais_asesmen', 'id')->where('id_sesi_asesmen', $idSesi),
            ],
            'kedaluwarsa_pada' => ['nullable', 'date', 'after:now'],
        ];
    }
}
