@extends('layouts.app')

@section('title', 'Token akses asesmen — ' . config('app.name'))

@section('content')
    <div class="mx-auto max-w-md">
        <x-ui.page-header title="Token akses asesmen">
            <x-slot:description>Masukkan token 8 karakter dari administrator untuk melihat asesmen yang ditugaskan kepada Anda.</x-slot:description>
        </x-ui.page-header>

        <form method="POST" action="{{ route('asesmen.token.verify') }}" class="card-depth space-y-4 p-6">
            @csrf
            <div>
                <label for="token_akses" class="block text-sm font-semibold text-on-surface">Token (8 karakter)</label>
                <input
                    type="text"
                    name="token_akses"
                    id="token_akses"
                    maxlength="8"
                    minlength="8"
                    required
                    autocomplete="off"
                    class="mt-2 w-full rounded-lg border border-outline-variant bg-surface-container-low px-4 py-3 text-center font-mono text-2xl uppercase tracking-[0.3em] text-on-surface"
                    placeholder="XXXXXXXX"
                >
                @error('token_akses')
                    <p class="mt-2 text-xs text-error">{{ $message }}</p>
                @enderror
            </div>
            <x-ui.button type="submit" variant="primary" class="w-full justify-center">Lanjut ke daftar asesmen</x-ui.button>
        </form>
    </div>
@endsection
