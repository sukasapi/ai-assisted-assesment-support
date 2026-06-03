@php
    use App\Enums\AssessmentSessionStatus;
@endphp
<form method="POST" action="{{ $action }}" class="card-depth max-w-xl space-y-4 p-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <x-ui.form-input label="Kode sesi" name="kode_sesi" :value="old('kode_sesi', $item?->kode_sesi)" required />
    <x-ui.form-input label="Nama" name="nama" :value="old('nama', $item?->nama)" required />
    <div class="grid grid-cols-2 gap-4">
        <x-ui.form-input label="Tanggal mulai" name="tanggal_mulai" type="date" :value="old('tanggal_mulai', $item?->tanggal_mulai?->format('Y-m-d'))" />
        <x-ui.form-input label="Tanggal selesai" name="tanggal_selesai" type="date" :value="old('tanggal_selesai', $item?->tanggal_selesai?->format('Y-m-d'))" />
    </div>
    <x-ui.form-select label="Status" name="status" required>
        @foreach (AssessmentSessionStatus::cases() as $s)
            <option value="{{ $s->value }}" @selected(old('status', $item?->status?->value ?? 'draf') === $s->value)>{{ $s->label() }}</option>
        @endforeach
    </x-ui.form-select>
    <div>
        <label for="catatan" class="block text-sm font-semibold text-on-surface">Catatan</label>
        <textarea name="catatan" id="catatan" rows="3" class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm">{{ old('catatan', $item?->catatan) }}</textarea>
    </div>
    <div class="flex gap-3">
        <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
        <x-ui.button href="{{ $item ? route('sesi-asesmen.show', $item) : route('sesi-asesmen.index') }}" variant="secondary">Batal</x-ui.button>
    </div>
</form>
