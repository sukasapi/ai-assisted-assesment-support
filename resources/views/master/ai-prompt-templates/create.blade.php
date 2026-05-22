@extends('layouts.app')

@section('title', 'Tambah template prompt AI — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.template-prompt-ai.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl font-semibold text-on-surface">Tambah template prompt AI</h1>
    </div>
    @include('master.ai-prompt-templates._form', [
        'action' => route('master.template-prompt-ai.store'),
        'method' => 'POST',
        'item' => null,
        'alatPenilaian' => $alatPenilaian,
        'alatTerpilih' => $alatTerpilih,
    ])
@endsection
