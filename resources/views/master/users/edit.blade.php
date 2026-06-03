@extends('layouts.app')

@section('title', 'Ubah pengguna — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.pengguna.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Ubah pengguna</h1>
    </div>
    @include('master.users._form', ['action' => route('master.pengguna.update', $item), 'method' => 'PUT', 'item' => $item])
@endsection
