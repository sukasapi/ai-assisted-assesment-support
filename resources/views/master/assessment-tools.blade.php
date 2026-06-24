@extends('layouts.app')

@section('title', 'Alat penilaian — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Alat penilaian</h1>
        </div>
        @if (auth()->user()?->isAdmin())
            <button type="button" data-open-modal="modal-alat-tambah" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</button>
        @endif
    </div>

    <x-ui.table-toolbar placeholder="Cari kode atau nama alat..." />

    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Aktif</th>
                    @if (auth()->user()?->isAdmin())
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse ($items as $row)
                    <tr class="hover:bg-surface-container-low/80">
                        <td class="px-4 py-3 font-mono text-on-surface">{{ $row->kode }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->nama }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->urutan }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()?->isAdmin())
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="text-on-surface-variant underline"
                                    data-open-modal="modal-alat-ubah"
                                    data-edit="{{ json_encode(['id' => $row->id, 'kode' => $row->kode, 'nama' => $row->nama, 'deskripsi' => $row->deskripsi, 'urutan' => $row->urutan, 'aktif' => $row->aktif], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                                >Ubah</button>
                                <form action="{{ route('master.alat-penilaian.destroy', $row) }}" method="POST" class="inline" data-swal-confirm="Alat penilaian yang dihapus tidak dapat dipulihkan." data-swal-confirm-title="Hapus alat penilaian?" data-swal-confirm-yes="Ya, hapus" data-swal-confirm-danger="1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-error underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->isAdmin() ? 5 : 4 }}" class="px-4 py-8 text-center text-sm text-on-surface-variant">Tidak ada data alat penilaian.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.table-pagination :paginator="$items" />

    @if (auth()->user()?->isAdmin())
        @include('master.partials.assessment-tool-modals')

        <script>
            (function () {
                const routes = {
                    update: @json(route('master.alat-penilaian.update', ['alatPenilaian' => 999999999])),
                };
                const replaceId = (url, id) => url.replace('999999999', String(id));
                const parseEditPayload = (raw) => {
                    if (!raw) return null;
                    try {
                        return JSON.parse(raw);
                    } catch {
                        const textarea = document.createElement('textarea');
                        textarea.innerHTML = raw;
                        try {
                            return JSON.parse(textarea.value);
                        } catch {
                            console.error('Gagal memuat data edit modal.');
                            return null;
                        }
                    }
                };
                const isTruthyField = (value) => [true, 1, '1', 'true', 'on', 'yes'].includes(value);

                document.querySelectorAll('[data-open-modal]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const modalId = btn.dataset.openModal;
                        const dialog = document.getElementById(modalId);
                        if (!dialog) return;

                        const editPayload = parseEditPayload(btn.dataset.edit);
                        if (modalId === 'modal-alat-ubah' && editPayload) {
                            dialog.querySelector('form')?.setAttribute('action', replaceId(routes.update, editPayload.id));
                            dialog.querySelector('[name="_edit_id"]').value = editPayload.id ?? '';
                            dialog.querySelector('[name="kode"]').value = editPayload.kode ?? '';
                            dialog.querySelector('[name="nama"]').value = editPayload.nama ?? '';
                            dialog.querySelector('[name="deskripsi"]').value = editPayload.deskripsi ?? '';
                            dialog.querySelector('[name="urutan"]').value = editPayload.urutan ?? '';
                            const aktif = dialog.querySelector('[name="aktif"][type="checkbox"]');
                            if (aktif) aktif.checked = isTruthyField(editPayload.aktif);
                        }

                        dialog.showModal?.();
                    });
                });

                document.querySelectorAll('[data-close-modal]').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        document.getElementById(btn.dataset.closeModal)?.close?.();
                    });
                });

                document.querySelectorAll('dialog[data-competency-modal]').forEach((dialog) => {
                    dialog.addEventListener('click', (e) => {
                        if (e.target === dialog) dialog.close();
                    });
                });

                @if ($errors->any())
                    @if (old('_method') === 'PUT' && old('_edit_id'))
                        (function () {
                            const dialog = document.getElementById('modal-alat-ubah');
                            const editId = @json(old('_edit_id'));
                            if (dialog && editId) {
                                dialog.querySelector('form')?.setAttribute('action', replaceId(routes.update, editId));
                                dialog.showModal?.();
                            }
                        })();
                    @else
                        document.getElementById('modal-alat-tambah')?.showModal?.();
                    @endif
                @endif
            })();
        </script>
    @endif
@endsection
