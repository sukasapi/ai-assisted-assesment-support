@foreach ($kelompokKompetensi as $group)
    <tr class="bg-zinc-100">
        <th scope="colgroup" class="sticky left-0 z-10 border-r border-b border-zinc-200 bg-zinc-100 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-700">
            {{ $group->nama }}
            <span class="ml-1 font-mono font-normal normal-case text-zinc-500">({{ $group->kode }})</span>
        </th>
        @foreach ($alat as $tool)
            <td class="border-b border-zinc-200 bg-zinc-100"></td>
        @endforeach
    </tr>
    @foreach ($group->competencies as $c)
        <tr @class(['hover:bg-zinc-50/80' => $editable])>
            <th scope="row" class="sticky left-0 z-10 border-r border-zinc-200 bg-white px-3 py-2 text-left font-normal text-zinc-800">
                <span class="font-mono text-xs">{{ $c->kode_kompetensi }}</span>
                <span class="mt-0.5 block truncate text-xs text-zinc-500" title="{{ $c->nama }}">{{ $c->nama }}</span>
            </th>
            @foreach ($alat as $tool)
                @php $cellKey = $c->id.'-'.$tool->id; @endphp
                <td class="px-1 py-1 text-center align-middle">
                    @if ($editable)
                        <label class="inline-flex size-9 cursor-pointer items-center justify-center rounded-md hover:bg-zinc-100">
                            <input type="checkbox" name="sel[]" value="{{ $cellKey }}" class="size-4 rounded border-zinc-300 text-zinc-900"
                                @checked($mapped->has($cellKey))>
                            <span class="sr-only">{{ $c->kode_kompetensi }} — {{ $tool->kode }}</span>
                        </label>
                    @else
                        <span class="text-zinc-500">
                            @if ($mapped->has($cellKey))
                                <span class="inline-flex size-8 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-800" title="Terpakai">✓</span>
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
