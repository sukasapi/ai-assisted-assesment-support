@extends('layouts.app')

@section('title', 'Log AI — ' . config('app.name'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('master.index') }}" class="text-sm text-zinc-600 hover:text-zinc-900">&larr; Master data</a>
        <h1 class="mt-2 text-2xl font-semibold text-zinc-900">Log AI</h1>
        <p class="mt-1 text-sm text-zinc-600">Monitoring panggilan AI (incremental dan bulk), termasuk status gagal/sukses.</p>
    </div>

    <section class="mb-4 rounded-lg border border-zinc-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="rounded bg-zinc-100 px-2 py-1 text-zinc-700">Failed jobs antrean: <strong>{{ $failedJobCount }}</strong></span>
        </div>
        <form method="GET" class="mt-3 grid gap-3 sm:grid-cols-3">
            <div>
                <label for="status" class="block text-xs font-medium text-zinc-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    <option value="berhasil" @selected(request('status') === 'berhasil')>Berhasil</option>
                    <option value="gagal" @selected(request('status') === 'gagal')>Gagal</option>
                </select>
            </div>
            <div>
                <label for="jalur" class="block text-xs font-medium text-zinc-700">Jalur</label>
                <select id="jalur" name="jalur" class="mt-1 w-full rounded-md border border-zinc-300 px-2 py-1.5 text-sm">
                    <option value="">Semua</option>
                    @foreach ($jalurTersedia as $j)
                        <option value="{{ $j }}" @selected(request('jalur') === $j)>{{ $j }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-md bg-zinc-900 px-3 py-1.5 text-sm text-white hover:bg-zinc-800">Terapkan filter</button>
            </div>
        </form>
    </section>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50 text-left text-xs font-medium uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">Waktu</th>
                    <th class="px-4 py-3">Jalur</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Model</th>
                    <th class="px-4 py-3">Pengguna</th>
                    <th class="px-4 py-3">Latency</th>
                    <th class="px-4 py-3">Usage</th>
                    <th class="px-4 py-3">Error</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($items as $row)
                    @php
                        $meta = is_array($row->metadata) ? $row->metadata : [];
                        $usage = is_array($meta['usage'] ?? null) ? $meta['usage'] : null;
                    @endphp
                    <tr class="hover:bg-zinc-50/80 align-top">
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-600">{{ $row->dibuat_pada?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $row->jalur }}</td>
                        <td class="px-4 py-3">
                            @if ($row->status === 'berhasil')
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-900">berhasil</span>
                            @else
                                <span class="rounded bg-rose-100 px-2 py-0.5 text-xs text-rose-900">gagal</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-700">{{ $row->nama_model ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-700">{{ $row->user?->nama ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-700">{{ isset($meta['latency_ms']) ? $meta['latency_ms'].' ms' : '—' }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-700">
                            @if ($usage)
                                p:{{ $usage['prompt_tokens'] ?? '?' }} / c:{{ $usage['completion_tokens'] ?? '?' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="max-w-sm whitespace-pre-wrap px-4 py-3 text-xs text-rose-700">{{ $row->pesan_kesalahan ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">Belum ada log AI.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $items->links() }}</div>
@endsection
