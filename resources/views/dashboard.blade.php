@extends('layouts.app')

@section('title', 'Dasbor — '.config('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-zinc-900">Dasbor</h1>
    <p class="mt-2 text-zinc-600">Anda masuk sebagai <strong>{{ auth()->user()->name }}</strong> (peran: {{ auth()->user()->role }}).</p>
    <p class="mt-4 text-sm text-zinc-600">Phase 1: fondasi database master, autentikasi, dan Sanctum siap. Fitur assessment dan matrix admin menyusul di Phase 2 setelah konfirmasi.</p>
@endsection
