<form method="POST" action="{{ $action }}" class="max-w-2xl space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div>
        <label for="kode" class="block text-sm font-medium text-on-surface">Kode</label>
        <input type="text" name="kode" id="kode" value="{{ old('kode', $item?->kode) }}" required maxlength="64" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm font-mono">
        <p class="mt-1 text-xs text-on-surface-variant">Contoh: STAR, CAR, UMUM</p>
    </div>
    <div>
        <label for="nama" class="block text-sm font-medium text-on-surface">Nama</label>
        <input type="text" name="nama" id="nama" value="{{ old('nama', $item?->nama) }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div>
        <label for="deskripsi" class="block text-sm font-medium text-on-surface">Deskripsi</label>
        <textarea name="deskripsi" id="deskripsi" rows="2" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">{{ old('deskripsi', $item?->deskripsi) }}</textarea>
    </div>
    <div>
        <label for="teks_instruksi" class="block text-sm font-medium text-on-surface">Instruksi untuk AI</label>
        <p class="mt-0.5 text-xs text-on-surface-variant">Digabung ke prompt sistem saat analisis bukti/payload. Kosongkan untuk template tanpa tambahan.</p>
        <textarea name="teks_instruksi" id="teks_instruksi" rows="14" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 font-mono text-sm">{{ old('teks_instruksi', $item?->teks_instruksi) }}</textarea>
    </div>
    <div>
        <span class="block text-sm font-medium text-on-surface">Alat penilaian (default master)</span>
        <p class="mt-0.5 text-xs text-on-surface-variant">Template ini otomatis dipakai untuk alat tercentang saat asesmen memakai mode «Ikuti master». Kosongkan jika hanya lewat pengaturan per asesmen.</p>
        <div class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-md border border-outline-variant bg-surface-container-low p-3">
            @forelse ($alatPenilaian ?? [] as $alat)
                <label class="flex cursor-pointer items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="id_alat_penilaian[]"
                        value="{{ $alat->id }}"
                        class="size-4 rounded border-outline-variant"
                        @checked(in_array($alat->id, $alatTerpilih ?? [], true))
                    >
                    <span><span class="font-mono text-primary">{{ $alat->kode }}</span> — {{ $alat->nama }}</span>
                </label>
            @empty
                <p class="text-xs text-on-surface-variant">Belum ada alat penilaian aktif.</p>
            @endforelse
        </div>
    </div>
    <div>
        <label for="urutan" class="block text-sm font-medium text-on-surface">Urutan</label>
        <input type="number" name="urutan" id="urutan" value="{{ old('urutan', $item?->urutan ?? 0) }}" min="0" required class="mt-1 w-full max-w-xs rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="aktif" value="0">
        <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded border-outline-variant" @checked(old('aktif', $item?->aktif ?? true))>
        <label for="aktif" class="text-sm text-on-surface">Aktif (dapat dipilih di asesmen)</label>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white hover:opacity-90">Simpan</button>
        <a href="{{ route('master.template-prompt-ai.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm text-on-surface">Batal</a>
    </div>
</form>
