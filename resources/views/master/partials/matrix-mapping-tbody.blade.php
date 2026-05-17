@foreach ($kelompokKompetensi as $group)
    <tr class="bg-surface-container">
        <th scope="colgroup" class="sticky left-0 z-10 border-r border-b border-outline-variant/40 bg-surface-container px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
            {{ $group->nama }}
            <span class="ml-1 font-mono font-normal normal-case text-on-surface-variant">({{ $group->kode }})</span>
        </th>
        @foreach ($alat as $tool)
            <td class="border-b border-outline-variant/40 bg-surface-container"></td>
        @endforeach
    </tr>
    @foreach ($group->competencies as $c)
        <tr @class(['hover:bg-surface-container-low/80' => $editable])>
            <th scope="row" class="sticky-col-header border-r border-outline-variant/40est px-3 py-2 text-left font-normal text-on-surface">
                <span class="font-mono text-xs">{{ $c->kode_kompetensi }}</span>
                <span class="mt-0.5 block truncate text-xs text-on-surface-variant" title="{{ $c->nama }}">{{ $c->nama }}</span>
            </th>
            @foreach ($alat as $tool)
                @php $cellKey = $c->id.'-'.$tool->id; @endphp
                <td class="px-1 py-1 text-center align-middle">
                    @if ($editable)
                        <label class="inline-flex size-9 cursor-pointer items-center justify-center rounded-md hover:bg-surface-container">
                            <input type="checkbox" name="sel[]" value="{{ $cellKey }}" class="size-4 rounded border-outline-variant text-on-surface"
                                @checked($mapped->has($cellKey))>
                            <span class="sr-only">{{ $c->kode_kompetensi }} — {{ $tool->kode }}</span>
                        </label>
                    @else
                        <span class="text-on-surface-variant">
                            @if ($mapped->has($cellKey))
                                <span class="inline-flex size-8 items-center justify-center rounded-full bg-surface-container-high text-xs font-medium text-on-surface" title="Terpakai">✓</span>
                            @else
                                <span class="inline-block size-8" aria-hidden="true"></span>
                            @endif
                        </span>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
@endforeach
