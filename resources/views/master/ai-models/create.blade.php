@extends('layouts.app')

@section('title', 'Tambah model AI — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.model-ai.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Tambah model AI</h1>
    </div>
    @include('master.ai-models._form', ['action' => route('master.model-ai.store'), 'method' => 'POST', 'item' => null])
@endsection
