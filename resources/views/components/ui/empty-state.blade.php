@props([
    'title' => 'Tidak ada data',
    'description' => null,
])

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <p class="text-sm font-medium text-zinc-700">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-md text-sm leading-relaxed text-zinc-500">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
