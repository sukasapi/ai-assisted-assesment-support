<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $model = $this->route('pengguna');

        return $model !== null && ($this->user()?->can('update', $model) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pengguna = $this->route('pengguna');

        return [
            'nama' => ['required', 'string', 'max:255'],
            'alamat_surel' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('ais_pengguna', 'alamat_surel')->ignore($pengguna?->id),
            ],
            'peran' => ['required', 'string', Rule::in(['admin', 'konsultan'])],
            'kata_sandi' => ['nullable', 'string', 'min:8', 'max:255'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['aktif' => $this->boolean('aktif')]);
    }
}
