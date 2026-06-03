<?php

namespace App\Http\Requests;

use App\Enums\AssessmentSessionStatus;
use App\Models\AssessmentSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssessmentSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sesi = $this->route('sesiAsesmen');

        return $sesi instanceof AssessmentSession && ($this->user()?->can('update', $sesi) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sesi = $this->route('sesiAsesmen');

        return [
            'kode_sesi' => [
                'required',
                'string',
                'max:64',
                Rule::unique('ais_sesi_asesmen', 'kode_sesi')->ignore($sesi?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', 'string', Rule::enum(AssessmentSessionStatus::class)],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
