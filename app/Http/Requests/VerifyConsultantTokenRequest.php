<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyConsultantTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'konsultan';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token_akses' => ['required', 'string', 'size:8', 'regex:/^[A-Z0-9]{8}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $token = strtoupper(preg_replace('/\s+/', '', (string) $this->input('token_akses', '')) ?? '');
        $this->merge(['token_akses' => $token]);
    }
}
