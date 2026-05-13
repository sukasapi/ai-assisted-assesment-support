@extends('layouts.app')

@section('title', 'Masuk — '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-md rounded-xl border border-zinc-200 bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-zinc-900">Masuk</h1>
        <p class="mt-1 text-sm text-zinc-600">AI Assessment Support</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-zinc-700">Kata sandi</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-1 focus:ring-zinc-900">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-300">
                Ingat saya
            </label>
            <button type="submit"
                class="w-full rounded-md bg-zinc-900 py-2 text-sm font-medium text-white hover:bg-zinc-800">Masuk</button>
        </form>
    </div>
@endsection
