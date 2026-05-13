@extends('layouts.app')

@section('title', 'Master data — ' . config('app.name'))

@section('content')
    <h1 class="text-2xl font-semibold text-zinc-900">Master data</h1>
    <p class="mt-2 max-w-2xl text-sm text-zinc-600">Referensi kamus kompetensi, alat penilaian, matriks, dan peserta. <strong>Admin</strong> dapat menambah, mengubah, dan menghapus (soft delete). <strong>Konsultan</strong> dapat melihat data.</p>

    <ul class="mt-8 grid gap-3 sm:grid-cols-2">
        <li><a href="{{ route('master.kelompok-kompetensi.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Kelompok kompetensi</a></li>
        <li><a href="{{ route('master.kompetensi.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Kompetensi</a></li>
        <li><a href="{{ route('master.tingkat-kompetensi.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Tingkat kompetensi</a></li>
        <li><a href="{{ route('master.alat-penilaian.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Alat penilaian</a></li>
        <li>
            <a href="{{ route('master.versi-matriks.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 shadow-sm hover:border-zinc-300">
                <span class="text-sm font-medium text-zinc-900">Versi matriks</span>
                <span class="mt-1 block text-xs font-normal text-zinc-500">Termasuk pemetaan kompetensi–alat per versi</span>
            </a>
        </li>
        <li><a href="{{ route('master.peserta.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Peserta</a></li>
        @if (auth()->user()->role === 'admin')
            <li><a href="{{ route('master.log-aktivitas.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Log aktivitas</a></li>
            <li><a href="{{ route('master.log-ai.index') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Log AI</a></li>
            <li><a href="{{ route('peserta.impor-csv') }}" class="block rounded-lg border border-zinc-200 bg-white p-4 text-sm font-medium text-zinc-900 shadow-sm hover:border-zinc-300">Impor peserta (CSV)</a></li>
        @endif
    </ul>
@endsection
