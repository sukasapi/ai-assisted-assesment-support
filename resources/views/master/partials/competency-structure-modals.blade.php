{{-- Modal tambah kelompok --}}
<dialog id="modal-kelompok-tambah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.kelompok-kompetensi.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Tambah kelompok kompetensi</h3>
                <p class="mt-0.5 text-xs text-on-surface-variant">Kategori tingkat atas struktur kompetensi.</p>
            </div>
            <button type="button" data-close-modal="modal-kelompok-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode kelompok" name="kode" :value="old('kode')" required maxlength="16" placeholder="Contoh: MNJ" />
            <x-ui.form-input label="Nama kelompok" name="nama" :value="old('nama')" required placeholder="Masukkan nama kelompok" />
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-kelompok-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

{{-- Modal ubah kelompok --}}
<dialog id="modal-kelompok-ubah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Ubah kelompok kompetensi</h3>
            </div>
            <button type="button" data-close-modal="modal-kelompok-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode kelompok" name="kode" required maxlength="16" />
            <x-ui.form-input label="Nama kelompok" name="nama" required />
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-kelompok-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

{{-- Modal tambah kompetensi --}}
<dialog id="modal-kompetensi-tambah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.kompetensi.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Tambah kompetensi</h3>
                <p class="mt-0.5 text-xs text-on-surface-variant">Lengkapi detail untuk entri kompetensi baru.</p>
            </div>
            <button type="button" data-close-modal="modal-kompetensi-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.form-input label="Kode kompetensi" name="kode_kompetensi" :value="old('kode_kompetensi')" required maxlength="32" placeholder="KMP-001" />
                <x-ui.form-select label="Kelompok" name="id_kelompok_kompetensi" required>
                    <option value="">— pilih —</option>
                    @foreach ($kelompok as $g)
                        <option value="{{ $g->id }}" @selected(old('id_kelompok_kompetensi') == $g->id)>{{ $g->kode }} — {{ $g->nama }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            <x-ui.form-input label="Nama kompetensi" name="nama" :value="old('nama')" required />
            <x-ui.form-textarea label="Definisi" name="definisi" :value="old('definisi')" rows="3" placeholder="Deskripsikan ruang lingkup kompetensi..." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.form-input label="Tingkat maksimum" name="tingkat_maksimum" type="number" :value="old('tingkat_maksimum', 6)" min="1" max="20" required />
                <div class="flex flex-col justify-end">
                    <span class="mb-2 text-sm font-semibold text-on-surface">Status</span>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="aktif" value="1" class="size-4" @checked(old('aktif', true))> Aktif
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="aktif" value="0" class="size-4" @checked(old('aktif') === '0' || old('aktif') === false)> Non-aktif
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-kompetensi-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                <span class="material-symbols-outlined text-base">save</span>
                Simpan
            </button>
        </footer>
    </form>
</dialog>

{{-- Modal ubah kompetensi --}}
<dialog id="modal-kompetensi-ubah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Ubah kompetensi</h3>
            </div>
            <button type="button" data-close-modal="modal-kompetensi-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.form-input label="Kode kompetensi" name="kode_kompetensi" required maxlength="32" />
                <x-ui.form-select label="Kelompok" name="id_kelompok_kompetensi" required>
                    @foreach ($kelompok as $g)
                        <option value="{{ $g->id }}">{{ $g->kode }} — {{ $g->nama }}</option>
                    @endforeach
                </x-ui.form-select>
            </div>
            <x-ui.form-input label="Nama kompetensi" name="nama" required />
            <x-ui.form-textarea label="Definisi" name="definisi" rows="3" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.form-input label="Tingkat maksimum" name="tingkat_maksimum" type="number" min="1" max="20" required />
                <div class="flex flex-col justify-end">
                    <span class="mb-2 text-sm font-semibold text-on-surface">Status</span>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="aktif" value="1" class="size-4"> Aktif</label>
                        <label class="flex items-center gap-2 text-sm"><input type="radio" name="aktif" value="0" class="size-4"> Non-aktif</label>
                    </div>
                </div>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-kompetensi-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

{{-- Modal tambah tingkat --}}
<dialog id="modal-tingkat-tambah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.tingkat-kompetensi.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Tambah tingkat kompetensi</h3>
                <p class="mt-0.5 text-xs text-on-surface-variant">Konfigurasi level kemahiran untuk kompetensi induk.</p>
            </div>
            <button type="button" data-close-modal="modal-tingkat-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-on-surface-variant">Kompetensi induk</label>
                <p class="mt-1 rounded-lg border border-outline-variant/50 bg-surface-container-low px-3 py-2 text-sm font-medium text-on-surface" data-kompetensi-label>—</p>
                <select name="id_kompetensi" class="mt-2 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm" required>
                    @foreach ($kelompok as $g)
                        @foreach ($g->competencies as $c)
                            <option value="{{ $c->id }}" @selected(old('id_kompetensi') == $c->id)>{{ $c->kode_kompetensi }} — {{ $c->nama }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <x-ui.form-input label="Tingkat / level" name="tingkat" type="number" :value="old('tingkat')" min="1" max="20" required />
            <x-ui.form-textarea label="Indikator perilaku" name="indikator_perilaku" :value="old('indikator_perilaku')" rows="4" required placeholder="Jelaskan indikator perilaku untuk level ini..." />
            <x-ui.form-input label="Etiket (opsional)" name="etiket" :value="old('etiket')" />
            <x-ui.form-textarea label="Deskripsi (opsional)" name="deskripsi" :value="old('deskripsi')" rows="2" />
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-tingkat-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                <span class="material-symbols-outlined text-base">save</span>
                Simpan
            </button>
        </footer>
    </form>
</dialog>

{{-- Modal ubah tingkat --}}
<dialog id="modal-tingkat-ubah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_id" value="">
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <div class="min-w-0 pr-2">
                <h3 class="text-lg font-bold text-on-surface">Ubah tingkat kompetensi</h3>
            </div>
            <button type="button" data-close-modal="modal-tingkat-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase text-on-surface-variant">Kompetensi induk</label>
                <p class="mt-1 rounded-lg border border-outline-variant/50 bg-surface-container-low px-3 py-2 text-sm text-on-surface" data-kompetensi-label>—</p>
                <input type="hidden" name="id_kompetensi" value="">
            </div>
            <x-ui.form-input label="Tingkat / level" name="tingkat" type="number" min="1" max="20" required />
            <x-ui.form-textarea label="Indikator perilaku" name="indikator_perilaku" rows="4" required />
            <x-ui.form-input label="Etiket (opsional)" name="etiket" />
            <x-ui.form-textarea label="Deskripsi (opsional)" name="deskripsi" rows="2" />
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-tingkat-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
