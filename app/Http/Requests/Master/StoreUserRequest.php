<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat_surel' => ['required', 'string', 'email', 'max:255', Rule::unique('ais_pengguna', 'alamat_surel')],
            'peran' => ['required', 'string', Rule::in(['admin', 'konsultan'])],
            'kata_sandi' => ['required', 'string', 'min:8', 'max:255'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['aktif' => $this->boolean('aktif', true)]);
    }
}
