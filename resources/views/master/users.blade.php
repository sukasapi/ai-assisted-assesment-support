@extends('layouts.app')

@section('title', 'Pengguna — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Pengguna</h1>
        </div>
        <button type="button" data-open-modal="modal-pengguna-tambah" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</button>
    </div>

    <x-ui.table-toolbar placeholder="Cari nama, email, atau peran..." />

    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Peran</th>
                    <th class="px-4 py-3">Aktif</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @foreach ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-semibold text-on-surface">{{ $row->nama }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->alamat_surel }}</td>
                        <td class="px-4 py-3 capitalize text-on-surface-variant">{{ $row->peran }}</td>
                        <td class="px-4 py-3">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button
                                type="button"
                                class="underline text-on-surface-variant"
                                data-open-modal="modal-pengguna-ubah"
                                data-edit="{{ json_encode(['id' => $row->id, 'nama' => $row->nama, 'alamat_surel' => $row->alamat_surel, 'peran' => $row->peran, 'aktif' => $row->aktif], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                            >Ubah</button>
                            @if ($row->id !== auth()->id())
                                <form action="{{ route('master.pengguna.destroy', $row) }}" method="POST" class="inline" data-swal-confirm="Pengguna akan dinonaktifkan dan tidak bisa login." data-swal-confirm-title="Nonaktifkan pengguna?" data-swal-confirm-yes="Ya, nonaktifkan" data-swal-confirm-danger="1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-error underline">Nonaktifkan</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-ui.table-pagination :paginator="$items" />

    @include('master.partials.user-modals')
    <x-ui.crud-modal-script
        :update-route="route('master.pengguna.update', ['pengguna' => 999999999])"
        create-modal-id="modal-pengguna-tambah"
        edit-modal-id="modal-pengguna-ubah"
    />
@endsection
