@extends('layouts.app')

@section('title', $sesi->nama . ' — ' . config('app.name'))

@section('content')
    <x-ui.page-header :title="$sesi->nama" :back-url="route('sesi-asesmen.index')" back-label="Daftar sesi">
        <x-slot:description>
            <span class="font-mono text-primary">{{ $sesi->kode_sesi }}</span>
            · {{ $sesi->status?->label() }}
            · {{ $sesi->assessments->count() }} asesmen
        </x-slot:description>
        <x-slot:actions>
            <x-ui.button
                type="button"
                variant="primary"
                data-open-modal="modal-asesmen-tambah"
                data-sesi-id="{{ $sesi->id }}"
                data-sesi-label="{{ $sesi->kode_sesi }} — {{ $sesi->nama }}"
            >Buat asesmen</x-ui.button>
            <x-ui.button type="button" variant="secondary" data-open-modal="modal-sesi-ubah" data-edit="{{ json_encode(['id' => $sesi->id, 'kode_sesi' => $sesi->kode_sesi, 'nama' => $sesi->nama, 'tanggal_mulai' => $sesi->tanggal_mulai?->format('Y-m-d'), 'tanggal_selesai' => $sesi->tanggal_selesai?->format('Y-m-d'), 'status' => $sesi->status?->value, 'catatan' => $sesi->catatan], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}">Ubah sesi</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('token_baru'))
        <div class="mb-6 rounded-xl border border-primary/30 bg-primary-fixed/30 p-4">
            <p class="text-sm font-semibold text-on-surface">Token akses konsultan (simpan sekarang):</p>
            <p class="mt-2 font-mono text-2xl tracking-widest text-primary">{{ session('token_baru') }}</p>
        </div>
    @endif

    <section class="card-depth mb-8 overflow-hidden rounded-xl">
        <div class="border-b border-outline-variant/30 px-6 py-4">
            <h2 class="text-section-header uppercase text-on-surface-variant">Asesmen dalam sesi</h2>
        </div>
        <div class="px-6 pt-4">
            <x-ui.table-client-filter target="tabel-asesmen-sesi" placeholder="Cari peserta atau status..." />
        </div>
        <table class="min-w-full text-sm" id="tabel-asesmen-sesi">
            <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                <tr>
                    <th class="px-6 py-3 text-left">Peserta</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse ($sesi->assessments as $a)
                    <tr>
                        <td class="px-6 py-4">{{ $a->participant?->nama_lengkap ?? '—' }}</td>
                        <td class="px-6 py-4">{{ $a->status?->value ?? '—' }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('asesmen.show', $a) }}" class="font-semibold text-primary hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-on-surface-variant">Belum ada asesmen.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="card-depth overflow-hidden rounded-xl">
        <div class="border-b border-outline-variant/30 px-6 py-4">
            <h2 class="text-section-header uppercase text-on-surface-variant">Penugasan konsultan (token 8 karakter)</h2>
        </div>

        <div class="p-6">
            <form method="POST" action="{{ route('sesi-asesmen.penugasan-konsultan.store', $sesi) }}" class="mb-8 space-y-4 rounded-lg border border-outline-variant/30 bg-surface-container-low p-4">
                @csrf
                <div>
                    <label class="block text-sm font-semibold">Konsultan</label>
                    <select name="id_pengguna" required class="mt-1 w-full max-w-md rounded-lg border border-outline-variant px-3 py-2 text-sm">
                        <option value="">— pilih —</option>
                        @foreach ($konsultanKandidat as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }} ({{ $k->alamat_surel }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <span class="block text-sm font-semibold">Asesmen yang dapat diakses</span>
                    <div class="mt-2 max-h-40 space-y-1 overflow-y-auto">
                        @foreach ($sesi->assessments as $a)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="id_asesmen[]" value="{{ $a->id }}" class="size-4 rounded">
                                {{ $a->participant?->nama_lengkap ?? 'Asesmen #'.$a->id }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold">Kedaluwarsa (opsional)</label>
                    <input type="datetime-local" name="kedaluwarsa_pada" class="mt-1 rounded-lg border border-outline-variant px-3 py-2 text-sm">
                </div>
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Buat penugasan &amp; token</button>
            </form>

            <x-ui.table-client-filter target="tabel-penugasan-sesi" placeholder="Cari konsultan atau token..." />

            <div class="overflow-x-auto rounded-lg border border-outline-variant/30">
                <table class="min-w-full text-sm" id="tabel-penugasan-sesi">
                    <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                        <tr>
                            <th class="px-4 py-3 text-left">Konsultan</th>
                            <th class="px-4 py-3 text-left">Token</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Asesmen</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/20">
                        @forelse ($sesi->consultantAssignments as $pen)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-on-surface">{{ $pen->consultant?->nama ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono tracking-wider text-primary">{{ $pen->token_akses }}</td>
                                <td class="px-4 py-3">
                                    @if ($pen->aktif)
                                        <span class="text-emerald-700">Aktif</span>
                                    @else
                                        <span class="text-on-surface-variant">Nonaktif</span>
                                    @endif
                                    @if ($pen->kedaluwarsa_pada)
                                        <span class="mt-0.5 block text-xs text-on-surface-variant">s/d {{ $pen->kedaluwarsa_pada->timezone(config('app.timezone'))->format('d M Y H:i') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-outline-variant/50 bg-surface-container-low px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:bg-surface-container"
                                        data-open-modal="modal-penugasan-{{ $pen->id }}"
                                    >
                                        <span class="material-symbols-outlined text-base">list</span>
                                        {{ $pen->assessments->count() }} asesmen
                                    </button>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        <form method="POST" action="{{ route('sesi-asesmen.penugasan-konsultan.regenerate', [$sesi, $pen]) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-semibold text-primary hover:underline">Regenerate</button>
                                        </form>
                                        @if ($pen->aktif)
                                            <form method="POST" action="{{ route('sesi-asesmen.penugasan-konsultan.nonaktif', [$sesi, $pen]) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-xs font-semibold text-error hover:underline">Nonaktifkan</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-on-surface-variant">Belum ada penugasan konsultan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @foreach ($sesi->consultantAssignments as $pen)
        <dialog id="modal-penugasan-{{ $pen->id }}" class="w-full max-w-lg rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-0 shadow-xl backdrop:bg-black/40">
            <div class="flex items-center justify-between border-b border-outline-variant/30 px-6 py-4">
                <div>
                    <h3 class="font-semibold text-on-surface">Asesmen dalam penugasan</h3>
                    <p class="mt-0.5 text-xs text-on-surface-variant">{{ $pen->consultant?->nama }} · token {{ $pen->token_akses }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-1 text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface"
                    data-close-modal="modal-penugasan-{{ $pen->id }}"
                    aria-label="Tutup"
                >
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
            <div class="max-h-80 overflow-y-auto px-6 py-4">
                @if ($pen->assessments->isEmpty())
                    <p class="text-sm text-on-surface-variant">Tidak ada asesmen yang ditautkan ke penugasan ini.</p>
                @else
                    <ul class="divide-y divide-outline-variant/20 text-sm">
                        @foreach ($pen->assessments as $a)
                            <li class="flex items-center justify-between gap-3 py-3">
                                <div>
                                    <p class="font-medium text-on-surface">{{ $a->participant?->nama_lengkap ?? 'Asesmen #'.$a->id }}</p>
                                    <p class="text-xs text-on-surface-variant">{{ $a->status?->value ?? '—' }} · {{ $a->matrixVersion?->kode_versi ?? '—' }}</p>
                                </div>
                                <a href="{{ route('asesmen.show', $a) }}" class="shrink-0 text-xs font-semibold text-primary hover:underline">Detail</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="border-t border-outline-variant/30 px-6 py-3 text-right">
                <button
                    type="button"
                    class="rounded-lg border border-outline-variant px-4 py-2 text-sm font-semibold text-on-surface hover:bg-surface-container-low"
                    data-close-modal="modal-penugasan-{{ $pen->id }}"
                >
                    Tutup
                </button>
            </div>
        </dialog>
    @endforeach

    @include('assessment-sessions.partials.session-modals')
    @can('create', App\Models\Assessment::class)
        @include('assessments.partials.asesmen-form-modals', ['bukaModalAsesmenSesi' => $bukaModalAsesmenSesi ?? null])
    @endcan
    <x-ui.crud-modal-script
        :update-route="route('sesi-asesmen.update', ['sesiAsesmen' => 999999999])"
        create-modal-id="modal-sesi-tambah"
        edit-modal-id="modal-sesi-ubah"
    />
@endsection
