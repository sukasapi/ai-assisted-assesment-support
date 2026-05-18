@props([
    'asesmen',
    'payload',
    'aiAktif' => false,
    'aiPesanNonaktif' => '',
    'aiModelOptions' => [],
    'aiModelDefault' => '',
    'aiAntrianAsync' => false,
])

@can('update', $asesmen)
    @if ($aiAktif)
        <form
            method="POST"
            action="{{ route('asesmen.payload-alat.analisis-ai', [$asesmen, $payload]) }}"
            class="js-ai-processing-form flex flex-col items-end gap-2 sm:flex-row sm:items-center"
            data-ai-mode="bulk"
        >
            @csrf
            @if (count($aiModelOptions) > 0)
                <label class="flex flex-col items-end gap-1 text-right">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">Model AI</span>
                    <select
                        name="nama_model"
                        class="max-w-[14rem] rounded-lg border border-outline-variant/40 bg-surface-container-lowest px-3 py-1.5 text-xs"
                    >
                        @foreach ($aiModelOptions as $opsi)
                            <option value="{{ $opsi['id'] }}" @selected($opsi['id'] === $aiModelDefault)>
                                {{ $opsi['label'] }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endif
            <button
                type="submit"
                {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-full bg-primary px-5 py-2 text-sm font-bold text-white hover:opacity-90']) }}
            >
                <span class="material-symbols-outlined text-sm">psychology</span>
                Analisis AI bulk
            </button>
        </form>
        <p class="max-w-xs text-right text-[11px] leading-snug text-on-surface-variant">
            @if ($aiAntrianAsync)
                Proses di background — halaman tidak perlu menunggu.
            @else
                Batas waktu per model berlaku; jika lambat, sistem mencoba model lain secara bergantian.
            @endif
        </p>
    @else
        <div class="flex max-w-xs flex-col items-end gap-1 sm:max-w-sm">
            <button
                type="button"
                disabled
                title="{{ $aiPesanNonaktif }}"
                class="flex cursor-not-allowed items-center gap-2 rounded-full bg-surface-container-high px-5 py-2 text-sm font-bold text-on-surface-variant/70"
            >
                <span class="material-symbols-outlined text-sm">psychology</span>
                Analisis AI bulk
            </button>
            <p class="text-right text-xs leading-snug text-amber-800">{{ $aiPesanNonaktif }}</p>
        </div>
    @endif
@endcan
