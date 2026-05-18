@props([
    'asesmen',
    'payload',
    'aiAktif' => false,
    'aiPesanNonaktif' => '',
])

@can('update', $asesmen)
    @if ($aiAktif)
        <form
            method="POST"
            action="{{ route('asesmen.payload-alat.analisis-ai', [$asesmen, $payload]) }}"
            class="js-ai-processing-form shrink-0"
            data-ai-mode="bulk"
        >
            @csrf
            <button
                type="submit"
                {{ $attributes->merge(['class' => 'flex items-center gap-2 rounded-full bg-primary px-5 py-2 text-sm font-bold text-white hover:opacity-90']) }}
            >
                <span class="material-symbols-outlined text-sm">psychology</span>
                Analisis AI bulk
            </button>
        </form>
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
