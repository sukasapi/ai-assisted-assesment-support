<dialog id="modal-model-ai-tambah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="{{ route('master.model-ai.store') }}" class="competency-modal__form">
        @csrf
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Tambah model AI</h3>
            <button type="button" data-close-modal="modal-model-ai-tambah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <x-ui.form-input label="ID model OpenRouter" name="id_model_openrouter" :value="old('id_model_openrouter')" required maxlength="191" />
            <p class="-mt-2 text-xs text-on-surface-variant">Contoh: google/gemini-2.0-flash-001</p>
            <x-ui.form-input label="Label tampilan" name="label" :value="old('label')" required />
            <x-ui.form-input label="Urutan" name="urutan" type="number" :value="old('urutan', 0)" min="0" required />
            <div class="flex items-center gap-2">
                <input type="hidden" name="utama" value="0">
                <input type="checkbox" name="utama" id="modal-model-ai-tambah-utama" value="1" class="size-4 rounded border-outline-variant" @checked(old('utama'))>
                <label for="modal-model-ai-tambah-utama" class="text-sm text-on-surface">Model utama (default)</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-model-ai-tambah-aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', true))>
                <label for="modal-model-ai-tambah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-model-ai-tambah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>

<dialog id="modal-model-ai-ubah" data-competency-modal data-modal-size="md" class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-xl">
    <form method="POST" action="#" class="competency-modal__form">
        @csrf
        @method('PUT')
        <header class="competency-modal__header flex items-start justify-between gap-3">
            <h3 class="text-lg font-bold text-on-surface">Ubah model AI</h3>
            <button type="button" data-close-modal="modal-model-ai-ubah" class="shrink-0 rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="competency-modal__body space-y-4">
            <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
            <x-ui.form-input label="ID model OpenRouter" name="id_model_openrouter" :value="old('id_model_openrouter')" required maxlength="191" />
            <x-ui.form-input label="Label tampilan" name="label" :value="old('label')" required />
            <x-ui.form-input label="Urutan" name="urutan" type="number" :value="old('urutan')" min="0" required />
            <div class="flex items-center gap-2">
                <input type="hidden" name="utama" value="0">
                <input type="checkbox" name="utama" id="modal-model-ai-ubah-utama" value="1" class="size-4 rounded border-outline-variant">
                <label for="modal-model-ai-ubah-utama" class="text-sm text-on-surface">Model utama (default)</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="aktif" value="0">
                <input type="checkbox" name="aktif" id="modal-model-ai-ubah-aktif" value="1" class="size-4 rounded border-outline-variant">
                <label for="modal-model-ai-ubah-aktif" class="text-sm text-on-surface">Aktif</label>
            </div>
        </div>
        <footer class="competency-modal__footer flex justify-end gap-3">
            <button type="button" data-close-modal="modal-model-ai-ubah" class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface">Batal</button>
            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Simpan</button>
        </footer>
    </form>
</dialog>
