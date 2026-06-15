<dialog id="modal-template-prompt-tambah" data-competency-modal data-modal-size="xl" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.template-prompt-ai.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Tambah template prompt AI</h3>
            <button type="button" data-close-modal="modal-template-prompt-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode" name="kode" :value="old('kode')" required maxlength="64" />
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <x-ui.form-textarea label="Deskripsi" name="deskripsi" :value="old('deskripsi')" rows="2" />
            <div>
                <label for="modal-template-prompt-tambah-instruksi" class="block text-xs font-semibold uppercase text-on-surface-variant">Instruksi untuk AI</label>
                <textarea name="teks_instruksi" id="modal-template-prompt-tambah-instruksi" rows="8" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 font-mono text-sm">{{ old('teks_instruksi') }}</textarea>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase text-on-surface-variant">Alat penilaian (default master)</span>
                <div class="mt-2 max-h-36 space-y-1 overflow-y-auto rounded-md border border-outline-variant bg-surface-container-low p-3">
                    @forelse ($alatPenilaian as $alat)
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="id_alat_penilaian[]" value="{{ $alat->id }}" class="size-4 rounded border-outline-variant" @checked(in_array($alat->id, old('id_alat_penilaian', []), true))>
                            <span><span class="font-mono text-primary">{{ $alat->kode }}</span> — {{ $alat->nama }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-on-surface-variant">Belum ada alat penilaian aktif.</p>
                    @endforelse
                </div>
            </div>
            <x-ui.form-input label="Urutan" name="urutan" type="number" :value="old('urutan', 0)" min="0" required />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-template-prompt-tambah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
                <label for="modal-template-prompt-tambah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-template-prompt-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="modal-template-prompt-ubah" data-competency-modal data-modal-size="xl" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Ubah template prompt AI</h3>
            <button type="button" data-close-modal="modal-template-prompt-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="Kode" name="kode" :value="old('kode')" required maxlength="64" />
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <x-ui.form-textarea label="Deskripsi" name="deskripsi" :value="old('deskripsi')" rows="2" />
            <div>
                <label for="modal-template-prompt-ubah-instruksi" class="block text-xs font-semibold uppercase text-on-surface-variant">Instruksi untuk AI</label>
                <textarea name="teks_instruksi" id="modal-template-prompt-ubah-instruksi" rows="8" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 font-mono text-sm">{{ old('teks_instruksi') }}</textarea>
            </div>
            <div>
                <span class="block text-xs font-semibold uppercase text-on-surface-variant">Alat penilaian (default master)</span>
                <div class="mt-2 max-h-36 space-y-1 overflow-y-auto rounded-md border border-outline-variant bg-surface-container-low p-3">
                    @forelse ($alatPenilaian as $alat)
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="id_alat_penilaian[]" value="{{ $alat->id }}" class="size-4 rounded border-outline-variant">
                            <span><span class="font-mono text-primary">{{ $alat->kode }}</span> — {{ $alat->nama }}</span>
                        </label>
                    @empty
                        <p class="text-xs text-on-surface-variant">Belum ada alat penilaian aktif.</p>
                    @endforelse
                </div>
            </div>
            <x-ui.form-input label="Urutan" name="urutan" type="number" :value="old('urutan')" min="0" required />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-template-prompt-ubah-aktif" value="1" class="size-4 rounded border-outline-variant">
                <label for="modal-template-prompt-ubah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-template-prompt-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
