<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportParticipantsCsvRequest extends FormRequest
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
        return [
            'berkas_csv' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
            'delimiter' => ['required', 'string', Rule::in([',', ';'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'berkas_csv' => 'berkas CSV',
            'delimiter' => 'delimiter',
        ];
    }

    protected function prepareForValidation(): void
    {
        $d = $this->input('delimiter');
        if ($d === 'comma' || $d === ',') {
            $this->merge(['delimiter' => ',']);
        } elseif ($d === 'semicolon' || $d === ';') {
            $this->merge(['delimiter' => ';']);
        }
    }
}
