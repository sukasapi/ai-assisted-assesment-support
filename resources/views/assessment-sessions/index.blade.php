@extends('layouts.app')

@section('title', 'Sesi assessment — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Sesi assessment">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" data-open-modal="modal-sesi-tambah">
                <span class="material-symbols-outlined text-lg">add</span>
                Buat sesi
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table-toolbar placeholder="Cari kode atau nama sesi..." />

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
                    <button
                        type="button"
                        class="mr-3 text-sm font-semibold text-on-surface-variant hover:underline"
                        data-open-modal="modal-sesi-ubah"
                        data-edit="{{ json_encode(['id' => $row->id, 'kode_sesi' => $row->kode_sesi, 'nama' => $row->nama, 'tanggal_mulai' => $row->tanggal_mulai?->format('Y-m-d'), 'tanggal_selesai' => $row->tanggal_selesai?->format('Y-m-d'), 'status' => $row->status?->value, 'catatan' => $row->catatan], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                    >Ubah</button>
                    <a href="{{ route('sesi-asesmen.show', $row) }}" class="font-semibold text-primary hover:underline">Detail</a>
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>
    <x-ui.table-pagination :paginator="$daftar" />

    @include('assessment-sessions.partials.session-modals')
    <x-ui.crud-modal-script
        :update-route="route('sesi-asesmen.update', ['sesiAsesmen' => 999999999])"
        create-modal-id="modal-sesi-tambah"
        edit-modal-id="modal-sesi-ubah"
    />
@endsection
