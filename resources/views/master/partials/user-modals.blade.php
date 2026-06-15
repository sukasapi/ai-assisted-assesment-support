<dialog id="modal-pengguna-tambah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.pengguna.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Tambah pengguna</h3>
            <button type="button" data-close-modal="modal-pengguna-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <x-ui.form-input label="Email" name="alamat_surel" type="email" :value="old('alamat_surel')" required />
            <div>
                <label for="modal-pengguna-tambah-peran" class="block text-xs font-semibold uppercase text-on-surface-variant">Peran</label>
                <select name="peran" id="modal-pengguna-tambah-peran" required class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                    <option value="admin" @selected(old('peran') === 'admin')>Admin</option>
                    <option value="konsultan" @selected(old('peran') === 'konsultan')>Konsultan</option>
                </select>
            </div>
            <x-ui.form-input label="Kata sandi" name="kata_sandi" type="password" required minlength="8" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-pengguna-tambah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
                <label for="modal-pengguna-tambah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-pengguna-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="modal-pengguna-ubah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Ubah pengguna</h3>
            <button type="button" data-close-modal="modal-pengguna-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <x-ui.form-input label="Email" name="alamat_surel" type="email" :value="old('alamat_surel')" required />
            <div>
                <label for="modal-pengguna-ubah-peran" class="block text-xs font-semibold uppercase text-on-surface-variant">Peran</label>
                <select name="peran" id="modal-pengguna-ubah-peran" required class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                    <option value="admin">Admin</option>
                    <option value="konsultan">Konsultan</option>
                </select>
            </div>
            <x-ui.form-input label="Kata sandi (kosongkan jika tidak diubah)" name="kata_sandi" type="password" minlength="8" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-pengguna-ubah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif'))>
                <label for="modal-pengguna-ubah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-pengguna-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
