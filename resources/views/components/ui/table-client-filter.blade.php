@props(['target', 'placeholder' => 'Filter baris tabel...'])

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    <label for="{{ $target }}-filter" class="block text-xs font-medium text-on-surface-variant">Cari</label>
    <input
        type="search"
        id="{{ $target }}-filter"
        data-table-filter="{{ $target }}"
        placeholder="{{ $placeholder }}"
        class="mt-1 w-full max-w-sm rounded-md border border-outline-variant bg-surface-container-lowest px-3 py-2 text-sm text-on-surface"
    >
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-table-filter]').forEach((input) => {
                if (input.dataset.bound) return;
                input.dataset.bound = '1';
                input.addEventListener('input', () => {
                    const table = document.getElementById(input.dataset.tableFilter);
                    if (!table) return;
                    const term = input.value.trim().toLowerCase();
                    table.querySelectorAll('tbody tr').forEach((tr) => {
                        tr.hidden = term !== '' && !tr.textContent.toLowerCase().includes(term);
                    });
                });
            });
        });
    </script>
@endonce
