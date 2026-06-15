{{-- Modal tambah alat penilaian --}}
<dialog id="modal-alat-tambah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.alat-penilaian.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Tambah alat penilaian</h3>
            </div>
            <button type="button" data-close-modal="modal-alat-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode" name="kode" :value="old('kode')" required maxlength="32" />
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <x-ui.form-textarea label="Deskripsi" name="deskripsi" :value="old('deskripsi')" rows="3" />
            <x-ui.form-input label="Urutan" name="urutan" type="number" :value="old('urutan', 0)" min="0" required />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-alat-tambah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
                <label for="modal-alat-tambah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-alat-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

{{-- Modal ubah alat penilaian --}}
<dialog id="modal-alat-ubah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Ubah alat penilaian</h3>
            </div>
            <button type="button" data-close-modal="modal-alat-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="Kode" name="kode" :value="old('kode')" required maxlength="32" />
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <x-ui.form-textarea label="Deskripsi" name="deskripsi" :value="old('deskripsi')" rows="3" />
            <x-ui.form-input label="Urutan" name="urutan" type="number" :value="old('urutan')" min="0" required />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-alat-ubah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif'))>
                <label for="modal-alat-ubah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-alat-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
