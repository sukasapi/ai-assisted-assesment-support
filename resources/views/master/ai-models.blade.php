@extends('layouts.app')

@section('title', 'Model AI — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('master.index') }}" class="text-sm text-on-surface-variant hover:text-primary">&larr; Master data</a>
            <h1 class="mt-2 text-2xl font-semibold text-on-surface">Model AI (OpenRouter)</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Pilih model utama (default) lewat radio; daftar aktif menggantikan .env bila ada data di sini.</p>
        </div>
        @if (auth()->user()?->isAdmin())
            <button type="button" data-open-modal="modal-model-ai-tambah" class="rounded-lg accent-gradient px-3 py-2 text-sm font-medium text-white hover:opacity-90">Tambah</button>
        @endif
    </div>

    <x-ui.table-toolbar placeholder="Cari ID model atau label..." />

    @if (auth()->user()?->isAdmin() && $items->isNotEmpty())
        <form id="form-model-ai-utama" method="POST" action="#" class="hidden">
            @csrf
            @method('PATCH')
            <input type="hidden" name="utama" value="1">
        </form>
    @endif

    <div class="overflow-hidden rounded-lg border border-outline-variant/40 bg-surface-container-lowest shadow-sm">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            <thead class="bg-surface-container-low text-left text-xs font-medium uppercase text-on-surface-variant">
                <tr>
                    <th class="w-12 px-3 py-3 text-center">
                        <span class="sr-only">Utama</span>
                    </th>
                    <th class="px-4 py-3">ID OpenRouter</th>
                    <th class="px-4 py-3">Label</th>
                    <th class="px-4 py-3">Urutan</th>
                    <th class="px-4 py-3">Aktif</th>
                    @if (auth()->user()?->isAdmin())
                        <th class="px-4 py-3 text-right">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/20">
                @forelse ($items as $row)
                    <tr class="hover:bg-surface-container-low/80 @if (! $row->aktif) opacity-60 @endif">
                        <td class="px-3 py-3 text-center align-middle">
                            @if (auth()->user()?->isAdmin())
                                <label class="inline-flex cursor-pointer items-center justify-center rounded-md p-1 hover:bg-surface-container">
                                    <input
                                        type="radio"
                                        form="form-model-ai-utama"
                                        name="model_utama"
                                        value="{{ $row->id }}"
                                        class="js-model-utama-radio size-4 border-outline-variant text-primary focus:ring-primary/30"
                                        data-set-url="{{ route('master.model-ai.set-utama', $row) }}"
                                        @checked($row->utama)
                                    >
                                    <span class="sr-only">Jadikan {{ $row->label }} model utama</span>
                                </label>
                            @else
                                <input
                                    type="radio"
                                    class="size-4 border-outline-variant text-primary"
                                    @checked($row->utama)
                                    disabled
                                    aria-label="{{ $row->utama ? 'Model utama: '.$row->label : $row->label }}"
                                >
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-on-surface">{{ $row->id_model_openrouter }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->label }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->urutan }}</td>
                        <td class="px-4 py-3 text-on-surface-variant">{{ $row->aktif ? 'Ya' : 'Tidak' }}</td>
                        @if (auth()->user()?->isAdmin())
                            <td class="px-4 py-3 text-right">
                                <button
                                    type="button"
                                    class="text-on-surface-variant underline"
                                    data-open-modal="modal-model-ai-ubah"
                                    data-edit="{{ json_encode(['id' => $row->id, 'id_model_openrouter' => $row->id_model_openrouter, 'label' => $row->label, 'urutan' => $row->urutan, 'aktif' => $row->aktif], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}"
                                >Ubah</button>
                                <form action="{{ route('master.model-ai.destroy', $row) }}" method="POST" class="inline" data-swal-confirm="Model AI yang dihapus tidak dapat dipulihkan." data-swal-confirm-title="Hapus model AI?" data-swal-confirm-yes="Ya, hapus" data-swal-confirm-danger="1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 text-error underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()?->isAdmin() ? 6 : 5 }}" class="px-4 py-8 text-center text-on-surface-variant">Belum ada model di database — daftar masih dari .env. Tambah model atau jalankan <code class="text-xs">php artisan db:seed --class=AiMasterSeeder</code>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.table-pagination :paginator="$items" />

    @if (auth()->user()?->isAdmin())
        @include('master.partials.ai-model-modals')
        <x-ui.crud-modal-script
            :update-route="route('master.model-ai.update', ['modelAi' => 999999999])"
            create-modal-id="modal-model-ai-tambah"
            edit-modal-id="modal-model-ai-ubah"
        />
        @if ($items->isNotEmpty())
            <script>
                (function () {
                    const form = document.getElementById('form-model-ai-utama');
                    if (!form) return;
                    document.querySelectorAll('.js-model-utama-radio').forEach((radio) => {
                        radio.addEventListener('change', () => {
                            if (!radio.checked) return;
                            const url = radio.dataset.setUrl;
                            if (!url) return;
                            form.setAttribute('action', url);
                            form.submit();
                        });
                    });
                })();
            </script>
        @endif
    @endif
@endsection
