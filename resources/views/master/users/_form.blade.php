<form method="POST" action="{{ $action }}" class="max-w-lg space-y-4 rounded-lg border border-outline-variant/40 bg-surface-container-lowest p-6 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <div>
        <label for="nama" class="block text-sm font-medium text-on-surface">Nama</label>
        <input type="text" name="nama" id="nama" value="{{ old('nama', $item?->nama) }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div>
        <label for="alamat_surel" class="block text-sm font-medium text-on-surface">Email</label>
        <input type="email" name="alamat_surel" id="alamat_surel" value="{{ old('alamat_surel', $item?->alamat_surel) }}" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div>
        <label for="peran" class="block text-sm font-medium text-on-surface">Peran</label>
        <select name="peran" id="peran" required class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
            <option value="admin" @selected(old('peran', $item?->peran) === 'admin')>Admin</option>
            <option value="konsultan" @selected(old('peran', $item?->peran) === 'konsultan')>Konsultan</option>
        </select>
    </div>
    <div>
        <label for="kata_sandi" class="block text-sm font-medium text-on-surface">Kata sandi {{ $item ? '(kosongkan jika tidak diubah)' : '' }}</label>
        <input type="password" name="kata_sandi" id="kata_sandi" {{ $item ? '' : 'required' }} minlength="8" class="mt-1 w-full rounded-md border border-outline-variant px-3 py-2 text-sm">
    </div>
    <div class="flex items-center gap-2">
        <input type="hidden" name="aktif" value="0">
        <input type="checkbox" name="aktif" id="aktif" value="1" class="size-4 rounded" @checked(old('aktif', $item?->aktif ?? true))>
        <label for="aktif" class="text-sm">Aktif</label>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="rounded-lg accent-gradient px-4 py-2 text-sm text-white">Simpan</button>
        <a href="{{ route('master.pengguna.index') }}" class="rounded-md border border-outline-variant px-4 py-2 text-sm">Batal</a>
    </div>
</form>
