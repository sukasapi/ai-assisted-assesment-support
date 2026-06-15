@extends('layouts.app')

@section('title', 'Kompetensi — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Kompetensi" :back-url="route('master.index')" back-label="Master data">
        <x-slot:description>Daftar kompetensi utama dalam organisasi — kelompok, kompetensi, dan tingkat dalam satu struktur.</x-slot:description>
        @if ($bolehUbah)
            <x-slot:actions>
                <x-ui.button type="button" variant="secondary" data-open-modal="modal-kelompok-tambah">Tambah kelompok</x-ui.button>
                <x-ui.button type="button" variant="primary" data-open-modal="modal-kompetensi-tambah">Tambah kompetensi</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.table-toolbar placeholder="Cari kelompok, kompetensi, atau tingkat..." />

    <section class="card-depth overflow-hidden rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" id="tabel-struktur-kompetensi">
                <thead class="bg-surface-container-low text-xs uppercase tracking-wide text-on-surface-variant">
                    <tr>
                        <th class="min-w-[280px] px-6 py-3 text-left">Struktur kompetensi</th>
                        <th class="px-6 py-3 text-left">Kode</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        @if ($bolehUbah)
                            <th class="px-6 py-3 text-right">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/20">
                    @forelse ($kelompok as $grup)
                        <tr
                            class="tree-row bg-surface-container-lowest hover:bg-surface-container-low/60"
                            data-tree-type="group"
                            data-tree-id="g-{{ $grup->id }}"
                            data-expandable="1"
                        >
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <button type="button" class="tree-toggle flex size-7 shrink-0 items-center justify-center rounded-md text-on-surface-variant hover:bg-surface-container" aria-label="Buka tutup">
                                        <span class="material-symbols-outlined text-lg tree-chevron transition-transform">chevron_right</span>
                                    </button>
                                    <span class="material-symbols-outlined text-lg text-primary">folder</span>
                                    <span class="font-semibold text-on-surface">{{ $grup->nama }}</span>
                                    <span class="text-xs text-on-surface-variant">({{ $grup->kode }})</span>
                                    @if ($bolehUbah)
                                        <button
                                            type="button"
                                            class="ml-1 flex size-6 items-center justify-center rounded-full border border-dashed border-primary/40 text-xs font-bold text-primary hover:bg-primary-fixed/30"
                                            title="Tambah kompetensi di kelompok ini"
                                            data-open-modal="modal-kompetensi-tambah"
                                            data-prefill-kelompok="{{ $grup->id }}"
                                        >+</button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-3 font-mono text-on-surface">{{ $grup->kode }}</td>
                            <td class="px-6 py-3">
                                <span class="rounded-md bg-primary-fixed/40 px-2 py-0.5 text-xs font-bold uppercase text-primary">Aktif</span>
                            </td>
                            @if ($bolehUbah)
                                <td class="px-6 py-3 text-right">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-primary hover:underline"
                                            data-open-modal="modal-kelompok-ubah"
                                            data-edit-id="{{ $grup->id }}"
                                        >Ubah</button>
                                    <form action="{{ route('master.kelompok-kompetensi.destroy', $grup) }}" method="POST" class="inline" data-swal-confirm="Kelompok kompetensi yang dihapus tidak dapat dipulihkan." data-swal-confirm-title="Hapus kelompok?" data-swal-confirm-yes="Ya, hapus" data-swal-confirm-danger="1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 text-xs font-semibold text-error hover:underline">Hapus</button>
                                    </form>
                                </td>
                            @endif
                        </tr>

                        @foreach ($grup->competencies as $kompetensi)
                            <tr
                                class="tree-row hidden bg-surface-container-lowest/50 hover:bg-surface-container-low/40"
                                data-tree-type="competency"
                                data-tree-id="c-{{ $kompetensi->id }}"
                                data-tree-parent="g-{{ $grup->id }}"
                                data-expandable="1"
                            >
                                <td class="px-6 py-3">
                                    <div class="flex items-center gap-2 pl-8">
                                        <button type="button" class="tree-toggle flex size-7 shrink-0 items-center justify-center rounded-md text-on-surface-variant hover:bg-surface-container" aria-label="Buka tutup">
                                            <span class="material-symbols-outlined text-lg tree-chevron transition-transform">chevron_right</span>
                                        </button>
                                        <span class="material-symbols-outlined text-lg text-on-surface-variant">psychology</span>
                                        <span class="font-medium text-on-surface">{{ $kompetensi->nama }}</span>
                                        @if ($bolehUbah)
                                            <button
                                                type="button"
                                                class="ml-1 flex size-6 items-center justify-center rounded-full border border-dashed border-primary/40 text-xs font-bold text-primary hover:bg-primary-fixed/30"
                                                title="Tambah tingkat"
                                                data-open-modal="modal-tingkat-tambah"
                                                data-prefill-kompetensi="{{ $kompetensi->id }}"
                                                data-prefill-kompetensi-nama="{{ $kompetensi->nama }}"
                                            >+</button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-3 font-mono text-on-surface">{{ $kompetensi->kode_kompetensi }}</td>
                                <td class="px-6 py-3">
                                    @if ($kompetensi->aktif)
                                        <span class="rounded-md bg-sky-100 px-2 py-0.5 text-xs font-bold uppercase text-sky-800">Ya</span>
                                    @else
                                        <span class="rounded-md bg-surface-container-high px-2 py-0.5 text-xs font-bold uppercase text-on-surface-variant">Tidak</span>
                                    @endif
                                </td>
                                @if ($bolehUbah)
                                    <td class="px-6 py-3 text-right">
                                        <button
                                            type="button"
                                            class="text-xs font-semibold text-primary hover:underline"
                                            data-open-modal="modal-kompetensi-ubah"
                                            data-edit-id="{{ $kompetensi->id }}"
                                        >Ubah</button>
                                        <form action="{{ route('master.kompetensi.destroy', $kompetensi) }}" method="POST" class="inline" data-swal-confirm="Kompetensi dan semua tingkat terkait akan dihapus." data-swal-confirm-title="Hapus kompetensi?" data-swal-confirm-yes="Ya, hapus" data-swal-confirm-danger="1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-2 text-xs font-semibold text-error hover:underline">Hapus</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>

                            @foreach ($kompetensi->levels as $tingkat)
                                <tr
                                    class="tree-row hidden hover:bg-surface-container-low/30"
                                    data-tree-type="level"
                                    data-tree-id="l-{{ $tingkat->id }}"
                                    data-tree-parent="c-{{ $kompetensi->id }}"
                                >
                                    <td class="px-6 py-3">
                                        <div class="flex items-center gap-2 pl-16">
                                            <span class="material-symbols-outlined text-base text-on-surface-variant/70">radio_button_unchecked</span>
                                            <span class="text-on-surface">
                                                Tingkat {{ $tingkat->tingkat }}@if ($tingkat->etiket): {{ $tingkat->etiket }}@endif
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 font-mono text-xs text-on-surface-variant">
                                        {{ $kompetensi->kode_kompetensi }}-{{ str_pad((string) $tingkat->tingkat, 2, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-6 py-3 text-on-surface-variant">—</td>
                                    @if ($bolehUbah)
                                        <td class="px-6 py-3 text-right">
                                            <button
                                                type="button"
                                                class="text-xs font-semibold text-primary hover:underline"
                                                data-open-modal="modal-tingkat-ubah"
                                                data-edit-id="{{ $tingkat->id }}"
                                            >Ubah</button>
                                            <form action="{{ route('master.tingkat-kompetensi.destroy', $tingkat) }}" method="POST" class="inline" data-swal-confirm="Tingkat kompetensi yang dihapus tidak dapat dipulihkan." data-swal-confirm-title="Hapus tingkat?" data-swal-confirm-yes="Ya, hapus" data-swal-confirm-danger="1">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-2 text-xs font-semibold text-error hover:underline">Hapus</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ $bolehUbah ? 4 : 3 }}" class="px-6 py-12 text-center text-on-surface-variant">
                                Belum ada struktur kompetensi.
                                @if ($bolehUbah)
                                    Mulai dengan menambah kelompok kompetensi.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-outline-variant/30 px-6 py-3 text-xs text-on-surface-variant">
            <span>Menampilkan {{ $jumlahBaris }} baris struktur</span>
            <button type="button" class="font-semibold text-primary hover:underline" id="tree-expand-all">Buka semua</button>
        </div>
    </section>

    @php
        $payloadKelompok = [];
        $payloadKompetensi = [];
        $payloadTingkat = [];
        if ($bolehUbah) {
            foreach ($kelompok as $grup) {
                $payloadKelompok[$grup->id] = [
                    'id' => $grup->id,
                    'kode' => $grup->kode,
                    'nama' => $grup->nama,
                ];
                foreach ($grup->competencies as $kompetensi) {
                    $payloadKompetensi[$kompetensi->id] = [
                        'id' => $kompetensi->id,
                        'id_kelompok_kompetensi' => $kompetensi->id_kelompok_kompetensi,
                        'kode_kompetensi' => $kompetensi->kode_kompetensi,
                        'nama' => $kompetensi->nama,
                        'definisi' => $kompetensi->definisi,
                        'tingkat_maksimum' => $kompetensi->tingkat_maksimum,
                        'aktif' => $kompetensi->aktif,
                    ];
                    foreach ($kompetensi->levels as $tingkat) {
                        $payloadTingkat[$tingkat->id] = [
                            'id' => $tingkat->id,
                            'id_kompetensi' => $tingkat->id_kompetensi,
                            'kompetensi_nama' => $kompetensi->nama,
                            'tingkat' => $tingkat->tingkat,
                            'indikator_perilaku' => $tingkat->indikator_perilaku,
                            'etiket' => $tingkat->etiket,
                            'deskripsi' => $tingkat->deskripsi,
                        ];
                    }
                }
            }
        }
    @endphp

    @if ($bolehUbah)
        @include('master.partials.competency-structure-modals', ['kelompok' => $kelompok])
    @endif

    <script>
        (function () {
            const routes = {
                kelompokUpdate: @json(route('master.kelompok-kompetensi.update', ['kelompokKompetensi' => 999999999])),
                kompetensiUpdate: @json(route('master.kompetensi.update', ['kompetensi' => 999999999])),
                tingkatUpdate: @json(route('master.tingkat-kompetensi.update', ['tingkatKompetensi' => 999999999])),
            };
            const replaceId = (url, id) => url.replace('999999999', String(id));
            const payloadKelompok = @json($payloadKelompok);
            const payloadKompetensi = @json($payloadKompetensi);
            const payloadTingkat = @json($payloadTingkat);

            const table = document.getElementById('tabel-struktur-kompetensi');
            if (!table) return;

            function setExpanded(toggleRow, open) {
                const id = toggleRow.dataset.treeId;
                if (!id) return;
                const chevron = toggleRow.querySelector('.tree-chevron');
                if (chevron) {
                    chevron.style.transform = open ? 'rotate(90deg)' : '';
                }
                table.querySelectorAll(`[data-tree-parent="${id}"]`).forEach((child) => {
                    if (open) {
                        child.classList.remove('hidden');
                    } else {
                        child.classList.add('hidden');
                        if (child.dataset.expandable === '1') {
                            setExpanded(child, false);
                        }
                    }
                });
            }

            function expandToNode(treeId) {
                if (!treeId) return;
                const path = [];
                let current = table.querySelector(`[data-tree-id="${treeId}"]`);
                while (current) {
                    path.unshift(current);
                    const parentId = current.dataset.treeParent;
                    current = parentId ? table.querySelector(`[data-tree-id="${parentId}"]`) : null;
                }
                path.forEach((row) => {
                    if (row.dataset.expandable === '1') {
                        setExpanded(row, true);
                    }
                });
            }

            const syncTextareaValue = (textarea, value) => {
                const nilai = String(value ?? '');
                textarea.value = nilai;
                const quill = textarea.__quill;
                if (quill) {
                    if (nilai.trim() === '') {
                        quill.setText('');
                    } else if (nilai.includes('<')) {
                        quill.clipboard.dangerouslyPasteHTML(nilai);
                    } else {
                        quill.setText(nilai);
                    }
                } else if (typeof window.applyTextToTextarea === 'function') {
                    window.applyTextToTextarea(textarea, nilai);
                    return;
                }
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            };

            const setFieldValue = (dialog, name, value) => {
                const fields = dialog.querySelectorAll(`[name="${name}"]`);
                if (!fields.length) return;
                const first = fields[0];
                if (first.type === 'radio') {
                    fields.forEach((r) => {
                        r.checked = String(value ? 1 : 0) === r.value;
                    });
                    return;
                }
                if (first.tagName === 'TEXTAREA') {
                    syncTextareaValue(first, value);
                    return;
                }
                first.value = value ?? '';
            };

            const resolveEditPayload = (btn, modalId) => {
                const editId = btn.dataset.editId;
                if (editId) {
                    if (modalId === 'modal-kelompok-ubah') return payloadKelompok?.[editId] ?? null;
                    if (modalId === 'modal-kompetensi-ubah') return payloadKompetensi?.[editId] ?? null;
                    if (modalId === 'modal-tingkat-ubah') return payloadTingkat?.[editId] ?? null;
                }
                return parseEditPayload(btn.dataset.edit);
            };

            table.querySelectorAll('.tree-toggle').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('tr');
                    if (!row) return;
                    const isHidden = table.querySelector(`[data-tree-parent="${row.dataset.treeId}"]`)?.classList.contains('hidden');
                    setExpanded(row, isHidden);
                });
            });

            document.getElementById('tree-expand-all')?.addEventListener('click', () => {
                table.querySelectorAll('[data-expandable="1"]').forEach((row) => setExpanded(row, true));
            });

            @php
                $expandNode = request()->query('expand');
                if ($errors->any()) {
                    if ($errors->has('tingkat') || $errors->has('indikator_perilaku')) {
                        $expandNode = old('id_kompetensi') ? 'c-' . old('id_kompetensi') : $expandNode;
                    } elseif ($errors->has('kode_kompetensi') || $errors->has('id_kelompok_kompetensi')) {
                        $expandNode = old('id_kelompok_kompetensi') ? 'g-' . old('id_kelompok_kompetensi') : $expandNode;
                    }
                }
            @endphp
            expandToNode(@json($expandNode));

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

            document.querySelectorAll('[data-open-modal]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const modalId = btn.dataset.openModal;
                    const dialog = document.getElementById(modalId);
                    if (!dialog) return;

                    if (modalId === 'modal-kompetensi-tambah' && btn.dataset.prefillKelompok) {
                        const sel = dialog.querySelector('[name="id_kelompok_kompetensi"]');
                        if (sel) sel.value = btn.dataset.prefillKelompok;
                    }
                    if (modalId === 'modal-tingkat-tambah') {
                        const sel = dialog.querySelector('[name="id_kompetensi"]');
                        const label = dialog.querySelector('[data-kompetensi-label]');
                        if (btn.dataset.prefillKompetensi && sel) {
                            sel.value = btn.dataset.prefillKompetensi;
                            if (label) label.textContent = btn.dataset.prefillKompetensiNama || '—';
                        }
                    }
                    const editPayload = resolveEditPayload(btn, modalId);

                    if (modalId === 'modal-kelompok-ubah' && editPayload) {
                        dialog.querySelector('form')?.setAttribute('action', replaceId(routes.kelompokUpdate, editPayload.id));
                        setFieldValue(dialog, 'kode', editPayload.kode);
                        setFieldValue(dialog, 'nama', editPayload.nama);
                    }
                    if (modalId === 'modal-kompetensi-ubah' && editPayload) {
                        dialog.querySelector('form')?.setAttribute('action', replaceId(routes.kompetensiUpdate, editPayload.id));
                        setFieldValue(dialog, 'id_kelompok_kompetensi', editPayload.id_kelompok_kompetensi);
                        setFieldValue(dialog, 'kode_kompetensi', editPayload.kode_kompetensi);
                        setFieldValue(dialog, 'nama', editPayload.nama);
                        setFieldValue(dialog, 'definisi', editPayload.definisi);
                        setFieldValue(dialog, 'tingkat_maksimum', editPayload.tingkat_maksimum);
                        setFieldValue(dialog, 'aktif', editPayload.aktif);
                    }
                    if (modalId === 'modal-tingkat-ubah' && editPayload) {
                        dialog.querySelector('form')?.setAttribute('action', replaceId(routes.tingkatUpdate, editPayload.id));
                        setFieldValue(dialog, '_edit_id', editPayload.id);
                        const label = dialog.querySelector('[data-kompetensi-label]');
                        if (label) label.textContent = editPayload.kompetensi_nama || '—';
                        setFieldValue(dialog, 'id_kompetensi', editPayload.id_kompetensi);
                        setFieldValue(dialog, 'tingkat', editPayload.tingkat);
                        setFieldValue(dialog, 'indikator_perilaku', editPayload.indikator_perilaku);
                        setFieldValue(dialog, 'etiket', editPayload.etiket);
                        setFieldValue(dialog, 'deskripsi', editPayload.deskripsi);
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
                @if ($errors->has('kode') && ! $errors->has('kode_kompetensi'))
                    document.getElementById('modal-kelompok-tambah')?.showModal?.();
                @elseif ($errors->has('kode_kompetensi') || $errors->has('id_kelompok_kompetensi'))
                    document.getElementById('modal-kompetensi-tambah')?.showModal?.();
                @elseif (($errors->has('tingkat') || $errors->has('indikator_perilaku')) && @json(old('_method')) === 'PUT')
                    (function () {
                        const dialog = document.getElementById('modal-tingkat-ubah');
                        const editId = @json(old('_edit_id'));
                        if (!dialog || !editId) return;
                        dialog.querySelector('form')?.setAttribute('action', replaceId(routes.tingkatUpdate, editId));
                        setFieldValue(dialog, '_edit_id', editId);
                        setFieldValue(dialog, 'id_kompetensi', @json(old('id_kompetensi')));
                        setFieldValue(dialog, 'tingkat', @json(old('tingkat')));
                        setFieldValue(dialog, 'indikator_perilaku', @json(old('indikator_perilaku')));
                        setFieldValue(dialog, 'etiket', @json(old('etiket')));
                        setFieldValue(dialog, 'deskripsi', @json(old('deskripsi')));
                        dialog.showModal?.();
                    })();
                @elseif ($errors->has('tingkat') || $errors->has('indikator_perilaku'))
                    document.getElementById('modal-tingkat-tambah')?.showModal?.();
                @endif
            @endif
        })();
    </script>
@endsection
