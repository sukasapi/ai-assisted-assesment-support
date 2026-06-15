@props(['asesorKandidat'])

<dialog id="modal-pilih-asesor" data-competency-modal data-modal-size="xl" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <div class="competency-modal__form">
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-on-surface">Pilih admin penilai</h3>
                <p class="mt-1 text-sm text-on-surface-variant">Centang admin yang ditugaskan menilai asesmen. Bisa memilih lebih dari satu.</p>
            </div>
            <button type="button" data-close-modal="modal-pilih-asesor" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input
                type="search"
                id="modal-pilih-asesor-cari"
                placeholder="Cari nama, surel, atau peran..."
                class="w-full rounded-lg border border-outline-variant px-3 py-2 text-sm"
                autocomplete="off"
            >
            <div class="max-h-[min(50dvh,28rem)] overflow-auto rounded-lg border border-outline-variant/40">
                <table class="min-w-full text-sm" id="tabel-pilih-asesor">
                    <thead class="sticky top-0 z-10 bg-surface-container-low text-left text-xs font-semibold uppercase text-on-surface-variant">
                        <tr>
                            <th class="w-10 px-3 py-3"></th>
                            <th class="px-3 py-3">Nama</th>
                            <th class="px-3 py-3">Surel</th>
                            <th class="px-3 py-3">Peran</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @foreach ($asesorKandidat as $u)
                            <tr
                                class="asesor-picker-row hover:bg-surface-container-low/80"
                                data-asesor-id="{{ $u->id }}"
                                data-asesor-nama="{{ $u->nama }}"
                                data-asesor-peran="{{ strtoupper($u->peran) }}"
                                data-asesor-surel="{{ $u->alamat_surel ?? '' }}"
                                data-search="{{ strtolower($u->nama.' '.$u->alamat_surel.' '.$u->peran) }}"
                            >
                                <td class="px-3 py-3">
                                    <input
                                        type="checkbox"
                                        class="asesor-picker-checkbox size-4 rounded border-outline-variant text-primary focus:ring-primary/20"
                                        value="{{ $u->id }}"
                                        data-asesor-checkbox
                                    >
                                </td>
                                <td class="px-3 py-3 font-semibold text-on-surface">{{ $u->nama }}</td>
                                <td class="px-3 py-3 text-on-surface-variant">{{ $u->alamat_surel ?: '—' }}</td>
                                <td class="px-3 py-3 text-xs font-bold uppercase text-on-surface-variant">{{ strtoupper($u->peran) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-on-surface-variant" data-asesor-picker-count>0 admin dipilih</p>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-pilih-asesor" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Selesai</button>
        </footer>
    </div>
</dialog>
