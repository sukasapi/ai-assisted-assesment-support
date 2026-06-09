@php
    use App\Enums\AssessmentSessionStatus;
@endphp

<dialog id="modal-sesi-tambah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('sesi-asesmen.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Buat sesi assessment</h3>
            <button type="button" data-close-modal="modal-sesi-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="Kode sesi" name="kode_sesi" :value="old('kode_sesi')" required />
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <div class="grid grid-cols-2 gap-4">
                <x-ui.form-input label="Tanggal mulai" name="tanggal_mulai" type="date" :value="old('tanggal_mulai')" />
                <x-ui.form-input label="Tanggal selesai" name="tanggal_selesai" type="date" :value="old('tanggal_selesai')" />
            </div>
            <div>
                <label for="modal-sesi-tambah-status" class="block text-xs font-semibold uppercase text-on-surface-variant">Status</label>
                <select name="status" id="modal-sesi-tambah-status" required class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                    @foreach (AssessmentSessionStatus::cases() as $s)
                        <option value="{{ $s->value }}" @selected(old('status', 'draf') === $s->value)>{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.form-textarea label="Catatan" name="catatan" :value="old('catatan')" rows="3" />
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-sesi-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="modal-sesi-ubah" data-competency-modal data-modal-size="lg" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Ubah sesi assessment</h3>
            <button type="button" data-close-modal="modal-sesi-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="Kode sesi" name="kode_sesi" :value="old('kode_sesi')" required />
            <x-ui.form-input label="Nama" name="nama" :value="old('nama')" required />
            <div class="grid grid-cols-2 gap-4">
                <x-ui.form-input label="Tanggal mulai" name="tanggal_mulai" type="date" :value="old('tanggal_mulai')" />
                <x-ui.form-input label="Tanggal selesai" name="tanggal_selesai" type="date" :value="old('tanggal_selesai')" />
            </div>
            <div>
                <label for="modal-sesi-ubah-status" class="block text-xs font-semibold uppercase text-on-surface-variant">Status</label>
                <select name="status" id="modal-sesi-ubah-status" required class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">
                    @foreach (AssessmentSessionStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
            <x-ui.form-textarea label="Catatan" name="catatan" :value="old('catatan')" rows="3" />
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-sesi-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
