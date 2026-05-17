@props([
    'empty' => 'Tidak ada data.',
    'colspan' => 1,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-outline-variant/40 bg-surface-container-lowest shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-outline-variant/30 text-sm">
            @isset($head)
                <thead class="bg-surface-container-low text-left text-xs font-bold uppercase tracking-wide text-on-surface-variant">
                    {{ $head }}
                </thead>
            @endisset
            <tbody class="divide-y divide-outline-variant/20 bg-surface-container-lowest">
                @if (trim($slot) === '')
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-4 py-8 text-center text-on-surface-variant">{{ $empty }}</td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
</div>
