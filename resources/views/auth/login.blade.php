@extends('layouts.app')

@section('title', 'Masuk — '.config('app.name'))

@section('content')
    <div class="w-full max-w-md animate-in fade-in">
        <div class="card-depth p-8">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-xl accent-gradient shadow-lg shadow-primary/20">
                    <span class="material-symbols-outlined text-[32px] text-on-primary" style="font-variation-settings: 'FILL' 1;">analytics</span>
                </div>
                <h1 class="font-display text-xl font-bold text-on-surface">Masuk</h1>
                <p class="mt-1 text-sm text-on-surface-variant">AI Assessment Support</p>
            </div>

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf
                <x-ui.form-input label="Email" name="email" type="email" value="{{ old('email') }}" required autofocus />
                <x-ui.form-input label="Kata sandi" name="password" type="password" required />
                <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                    <input type="checkbox" name="remember" value="1" class="size-4 rounded border-outline-variant text-primary focus:ring-primary/20">
                    Ingat saya
                </label>
                <x-ui.button type="submit" variant="primary" class="w-full">
                    Masuk
                </x-ui.button>
            </form>
        </div>
    </div>
@endsection
