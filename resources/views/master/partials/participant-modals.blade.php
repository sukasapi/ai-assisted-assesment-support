<dialog id="modal-peserta-tambah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.peserta.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Tambah peserta</h3>
            <button type="button" data-close-modal="modal-peserta-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode peserta" name="kode_peserta" :value="old('kode_peserta')" required />
            <x-ui.form-input label="Nama lengkap" name="nama_lengkap" :value="old('nama_lengkap')" required />
            <x-ui.form-input label="Alamat surel" name="alamat_surel" type="email" :value="old('alamat_surel')" />
            <x-ui.form-input label="Jabatan" name="jabatan" :value="old('jabatan')" />
            <x-ui.form-input label="Pendidikan" name="pendidikan" :value="old('pendidikan')" />
            <x-ui.form-input label="Tanggal lahir" name="tanggal_lahir" type="date" :value="old('tanggal_lahir')" />
            <div>
                <label for="modal-peserta-tambah-matriks" class="block text-xs font-semibold uppercase text-on-surface-variant">Versi matriks (opsional)</label>
                <select name="id_versi_matriks" id="modal-peserta-tambah-matriks" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                    <option value="">— tidak ada —</option>
                    @foreach ($versiMatriks as $v)
                        <option value="{{ $v->id }}" @selected(old('id_versi_matriks') == $v->id)>{{ $v->kode_versi }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.form-textarea label="Catatan" name="catatan" :value="old('catatan')" rows="2" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-peserta-tambah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
                <label for="modal-peserta-tambah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-peserta-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="modal-peserta-ubah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Ubah peserta</h3>
            <button type="button" data-close-modal="modal-peserta-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="Kode peserta" name="kode_peserta" :value="old('kode_peserta')" required />
            <x-ui.form-input label="Nama lengkap" name="nama_lengkap" :value="old('nama_lengkap')" required />
            <x-ui.form-input label="Alamat surel" name="alamat_surel" type="email" :value="old('alamat_surel')" />
            <x-ui.form-input label="Jabatan" name="jabatan" :value="old('jabatan')" />
            <x-ui.form-input label="Pendidikan" name="pendidikan" :value="old('pendidikan')" />
            <x-ui.form-input label="Tanggal lahir" name="tanggal_lahir" type="date" :value="old('tanggal_lahir')" />
            <div>
                <label for="modal-peserta-ubah-matriks" class="block text-xs font-semibold uppercase text-on-surface-variant">Versi matriks (opsional)</label>
                <select name="id_versi_matriks" id="modal-peserta-ubah-matriks" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                    <option value="">— tidak ada —</option>
                    @foreach ($versiMatriks as $v)
                        <option value="{{ $v->id }}">{{ $v->kode_versi }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.form-textarea label="Catatan" name="catatan" :value="old('catatan')" rows="2" />
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-peserta-ubah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif'))>
                <label for="modal-peserta-ubah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-peserta-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
