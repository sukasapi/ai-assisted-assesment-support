<?php

namespace App\Http\Requests;

use App\Enums\AssessmentSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssessmentSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\AssessmentSession::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kode_sesi' => ['required', 'string', 'max:64', Rule::unique('ais_sesi_asesmen', 'kode_sesi')],
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', 'string', Rule::enum(AssessmentSessionStatus::class)],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
