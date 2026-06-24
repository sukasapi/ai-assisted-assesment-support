@extends('layouts.app')

@section('title', 'Token API — '.config('app.name'))

@section('content')
    <x-ui.page-header title="Token API" :back-url="route('master.index')" back-label="Master data">
        <x-slot:description>
            Token Bearer untuk <strong>API publik v1</strong> (read-only). Setiap token mewakili akses sistem ke hasil asesmen.
        </x-slot:description>
    </x-ui.page-header>

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
    @endif

    @if ($tokenBaru)
        <div class="mb-6 rounded-xl border border-primary/30 bg-primary-fixed/30 p-5">
            <p class="text-sm font-semibold text-on-surface">Token baru (salin sekarang — hanya ditampilkan sekali):</p>
            <div class="mt-2 flex items-center gap-3">
                <code id="token-baru" class="block flex-1 overflow-x-auto rounded-lg bg-surface-container-lowest px-4 py-3 font-mono text-sm text-primary">{{ $tokenBaru }}</code>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('token-baru').innerText); this.textContent='Tersalin';"
                    class="shrink-0 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-on-primary hover:opacity-90">Salin</button>
            </div>
            <p class="mt-3 text-xs text-on-surface-variant">Gunakan pada header: <code class="font-mono">Authorization: Bearer &lt;token&gt;</code></p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card-depth rounded-xl bg-surface-container-lowest p-6 lg:col-span-1">
            <h2 class="text-section-header uppercase text-on-surface-variant">Buat token</h2>
            <form method="POST" action="{{ route('master.token-api.store') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="nama" class="text-xs font-medium text-on-surface-variant">Nama / tujuan token</label>
                    <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required maxlength="120"
                        placeholder="mis. Integrasi HRIS"
                        class="mt-1 w-full rounded-lg border border-outline-variant px-3 py-2 text-sm @error('nama') border-error @enderror">
                    @error('nama')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <p class="text-xs text-on-surface-variant">Kemampuan: <span class="font-mono">read</span> (akses baca). Cabut kapan saja.</p>
                <x-ui.button type="submit" variant="primary" class="w-full">Buat token</x-ui.button>
            </form>

            <div class="mt-6 rounded-lg border border-outline-variant/30 bg-surface-container-low p-4 text-xs text-on-surface-variant">
                <p class="font-semibold text-on-surface">Cara pakai</p>
                <p class="mt-1">Base URL: <code class="font-mono">{{ url('/api/v1') }}</code></p>
                <p class="mt-1">Contoh: <code class="font-mono">GET /api/v1/asesmen</code> dengan header <code class="font-mono">Authorization: Bearer …</code></p>
                <p class="mt-1">Lihat <code class="font-mono">documentation/api_publik.md</code> untuk daftar endpoint.</p>
            </div>
        </section>

        <section class="card-depth overflow-hidden rounded-xl bg-surface-container-lowest lg:col-span-2">
            <div class="border-b border-outline-variant/30 px-6 py-4">
                <h2 class="text-section-header uppercase text-on-surface-variant">Token aktif ({{ $tokens->count() }})</h2>
            </div>
            @if ($tokens->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-on-surface-variant">Belum ada token. Buat token pertama Anda di samping.</p>
            @else
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-container-low text-xs uppercase text-on-surface-variant">
                        <tr>
                            <th class="px-6 py-3 text-left">Nama</th>
                            <th class="px-6 py-3 text-left">Pemilik</th>
                            <th class="px-6 py-3 text-left">Terakhir dipakai</th>
                            <th class="px-6 py-3 text-left">Dibuat</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/15">
                        @foreach ($tokens as $t)
                            <tr>
                                <td class="px-6 py-4 font-medium text-on-surface">{{ $t->name }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $t->tokenable?->nama ?? '—' }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $t->last_used_at ? $t->last_used_at->timezone(config('app.timezone'))->diffForHumans() : 'belum pernah' }}</td>
                                <td class="px-6 py-4 text-on-surface-variant">{{ $t->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-right">
                                    <form method="POST" action="{{ route('master.token-api.destroy', $t) }}" onsubmit="return confirm('Cabut token «{{ $t->name }}»? Klien yang memakainya akan kehilangan akses.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-md border border-error/30 bg-error-container/30 px-3 py-1.5 text-xs font-bold text-on-error-container hover:bg-error-container/60">Cabut</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
