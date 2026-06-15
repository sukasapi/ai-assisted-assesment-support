<dialog id="modal-matriks-tambah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.versi-matriks.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Tambah versi matriks</h3>
            <button type="button" data-close-modal="modal-matriks-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode versi" name="kode_versi" :value="old('kode_versi')" required maxlength="64" />
            <x-ui.form-input label="Nama versi" name="nama_versi" :value="old('nama_versi')" required />
            <x-ui.form-input label="Kunci kamus" name="kunci_kamus" :value="old('kunci_kamus')" maxlength="64" />
            <x-ui.form-textarea label="Catatan konteks" name="catatan_konteks" :value="old('catatan_konteks')" rows="2" />
            <x-ui.form-input label="Dipublikasikan pada (opsional)" name="dipublikasikan_pada" type="datetime-local" :value="old('dipublikasikan_pada')" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-matriks-tambah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
                <label for="modal-matriks-tambah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="bawaan" value="0">
                <input type="checkbox" name="bawaan" id="modal-matriks-tambah-bawaan" value="1" class="size-4 rounded border-outline-variant" @checked(old('bawaan'))>
                <label for="modal-matriks-tambah-bawaan" class="text-sm text-on-surface">Bawaan</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-matriks-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="modal-matriks-ubah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Ubah versi matriks</h3>
            <button type="button" data-close-modal="modal-matriks-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="Kode versi" name="kode_versi" :value="old('kode_versi')" required maxlength="64" />
            <x-ui.form-input label="Nama versi" name="nama_versi" :value="old('nama_versi')" required />
            <x-ui.form-input label="Kunci kamus" name="kunci_kamus" :value="old('kunci_kamus')" maxlength="64" />
            <x-ui.form-textarea label="Catatan konteks" name="catatan_konteks" :value="old('catatan_konteks')" rows="2" />
            <x-ui.form-input label="Dipublikasikan pada (opsional)" name="dipublikasikan_pada" type="datetime-local" :value="old('dipublikasikan_pada')" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-matriks-ubah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif'))>
                <label for="modal-matriks-ubah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="bawaan" value="0">
                <input type="checkbox" name="bawaan" id="modal-matriks-ubah-bawaan" value="1" class="size-4 rounded border-outline-variant" @checked(old('bawaan'))>
                <label for="modal-matriks-ubah-bawaan" class="text-sm text-on-surface">Bawaan</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-matriks-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
