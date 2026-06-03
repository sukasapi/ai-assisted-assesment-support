@extends('layouts.app')

@section('title', 'Sesi assessment — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Sesi assessment">
        <x-slot:actions>
            <x-ui.button href="{{ route('sesi-asesmen.create') }}" variant="primary">
                <span class="material-symbols-outlined text-lg">add</span>
                Buat sesi
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.data-table :colspan="5" empty="Belum ada sesi.">
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Kode</th>
                <th class="px-4 py-3">Nama</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Asesmen</th>
                <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
        </x-slot:head>
        @foreach ($daftar as $row)
            <tr class="hover:bg-surface-container-low/50">
                <td class="px-4 py-3 font-mono text-sm">{{ $row->kode_sesi }}</td>
                <td class="px-4 py-3 font-semibold">{{ $row->nama }}</td>
                <td class="px-4 py-3">{{ $row->status?->label() ?? $row->status }}</td>
                <td class="px-4 py-3">{{ $row->assessments_count }}</td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('sesi-asesmen.show', $row) }}" class="font-semibold text-primary hover:underline">Detail</a>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
    <div class="mt-4">{{ $daftar->links() }}</div>
@endsection
