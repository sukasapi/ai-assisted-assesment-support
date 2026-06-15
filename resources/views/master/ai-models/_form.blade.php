<form method="POST" action="{{ $action }}" class="max-w-lg space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div>
        <label for="id_model_openrouter" class="block text-sm font-medium text-on-surface">ID model OpenRouter</label>
        <input type="text" name="id_model_openrouter" id="id_model_openrouter" value="{{ old('id_model_openrouter', $item?->id_model_openrouter) }}" required maxlength="191" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 font-mono text-sm">
        <p class="mt-1 text-xs text-on-surface-variant">Contoh: google/gemini-2.0-flash-001</p>
    </div>
    <div>
        <label for="label" class="block text-sm font-medium text-on-surface">Label tampilan</label>
        <input type="text" name="label" id="label" value="{{ old('label', $item?->label) }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div>
        <label for="urutan" class="block text-sm font-medium text-on-surface">Urutan</label>
        <input type="number" name="urutan" id="urutan" value="{{ old('urutan', $item?->urutan ?? 0) }}" min="0" required class="mt-1 w-full max-w-xs rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="aktif" value="0">
        <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', $item?->aktif ?? true))>
        <label for="aktif" class="text-sm text-on-surface">Aktif</label>
    </div>
    @if ($item === null)
        <p class="text-xs text-on-surface-variant">Model utama dipilih lewat radio di tabel daftar.</p>
    @endif
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
        <a href="{{ route('master.model-ai.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
    </div>
</form>
