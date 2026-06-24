@extends('layouts.app')

@section('title', 'Versi matriks — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Versi matriks</h1>
        </div>
        @if (auth()->user()?->isAdmin())
            <button type="button" data-open-modal="modal-matriks-tambah" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</button>
        @endif
    </div>

    <x-ui.table-toolbar placeholder="Cari kode atau nama versi..." />

    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Aktif</th>
                    <th class="px-4 py-3">Bawaan</th>
                    <th class="px-4 py-3">Pemetaan</th>
                    <th class="px-4 py-3">Rekomendasi</th>
                    <th class="px-4 py-3">Target</th>
                    @if (auth()->user()?->isAdmin())
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @foreach ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->kode_versi }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->nama_versi }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->bawaan ? 'Ya' : 'Tidak' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.button
                                href="{{ route('master.versi-matriks.pemetaan.index', $row) }}"
                                variant="secondary"
                                class="!px-3 !py-1.5 text-xs"
                            >Buka</x-ui.button>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.button
                                href="{{ route('master.versi-matriks.konfigurasi-rekomendasi.index', $row) }}"
                                variant="secondary"
                                class="!px-3 !py-1.5 text-xs"
                            >Atur</x-ui.button>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.button
                                href="{{ route('master.versi-matriks.target-kompetensi.index', $row) }}"
                                variant="secondary"
                                class="!px-3 !py-1.5 text-xs"
                            >Atur</x-ui.button>
                        </td>
                        @if (auth()->user()?->isAdmin())
                            <td class="px-4 py-3 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <x-ui.button
                                        type="button"
                                        variant="secondary"
                                        class="!px-3 !py-1.5 text-xs"
                                        data-open-modal="modal-matriks-ubah"
                                        data-edit="{{ json_encode(['id' => $row->id, 'kode_versi' => $row->kode_versi, 'nama_versi' => $row->nama_versi, 'kunci_kamus' => $row->kunci_kamus, 'catatan_konteks' => $row->catatan_konteks, 'dipublikasikan_pada' => $row->dipublikasikan_pada?->format('Y-m-d\TH:i'), 'aktif' => $row->aktif, 'bawaan' => $row->bawaan], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                                    >Ubah</x-ui.button>
                                    <form action="{{ route('master.versi-matriks.destroy', $row) }}" method="POST" class="inline" data-swal-confirm="Versi matriks yang dihapus tidak dapat dipulihkan." data-swal-confirm-title="Hapus versi matriks?" data-swal-confirm-yes="Ya, hapus" data-swal-confirm-danger="1">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button type="submit" variant="danger" class="!px-3 !py-1.5 text-xs">Hapus</x-ui.button>
                                    </form>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-ui.table-pagination :paginator="$items" />

    @if (auth()->user()?->isAdmin())
        @include('master.partials.matrix-version-modals')
        <x-ui.crud-modal-script
            :update-route="route('master.versi-matriks.update', ['versiMatriks' => 999999999])"
            create-modal-id="modal-matriks-tambah"
            edit-modal-id="modal-matriks-ubah"
        />
    @endif
@endsection
