@props(['peserta'])

<dialog id="modal-pilih-peserta" data-competency-modal data-modal-size="xl" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <div class="competency-modal__form">
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-on-surface">Pilih peserta</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Centang peserta yang akan dibuatkan asesmen. Bisa memilih lebih dari satu.</p>
            </div>
            <button type="button" data-close-modal="modal-pilih-peserta" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input
                type="search"
                id="modal-pilih-peserta-cari"
                placeholder="Cari nama, kode, atau surel..."
                class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm"
                autocomplete="off"
            >
            <div class="max-h-[min(50dvh,28rem)] overflow-auto rounded-lg border border-outline-variant/40">
                <table class="min-w-full text-sm" id="tabel-pilih-peserta">
                    <thead class="sticky top-0 z-10 bg-surface-container-low text-left text-xs font-semibold uppercase text-on-surface-variant">
                        <tr>
                            <th class="w-10 px-3 py-3"></th>
                            <th class="px-3 py-3">Nama</th>
                            <th class="px-3 py-3">Kode</th>
                            <th class="px-3 py-3">Surel</th>
                            <th class="px-3 py-3 text-center">Asesmen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @foreach ($peserta as $p)
                            <tr
                                class="peserta-picker-row hover:bg-surface-container-low/80"
                                data-peserta-id="{{ $p->id }}"
                                data-peserta-nama="{{ $p->nama_lengkap }}"
                                data-peserta-kode="{{ $p->kode_peserta }}"
                                data-peserta-surel="{{ $p->alamat_surel ?? '' }}"
                                data-search="{{ strtolower($p->nama_lengkap.' '.$p->kode_peserta.' '.($p->alamat_surel ?? '')) }}"
                            >
                                <td class="px-3 py-3">
                                    <input
                                        type="checkbox"
                                        class="peserta-picker-checkbox size-4 rounded border-outline-variant text-primary focus:ring-primary/20"
                                        value="{{ $p->id }}"
                                        data-peserta-checkbox
                                    >
                                </td>
                                <td class="px-3 py-3 font-semibold text-on-surface">{{ $p->nama_lengkap }}</td>
                                <td class="px-3 py-3 font-mono text-xs text-on-surface-variant">{{ $p->kode_peserta }}</td>
                                <td class="px-3 py-3 text-on-surface-variant">{{ $p->alamat_surel ?: '—' }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-on-surface">{{ $p->assessments_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-on-surface-variant" data-peserta-picker-count>0 peserta dipilih</p>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-pilih-peserta" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Selesai</button>
        </footer>
    </div>
</dialog>
