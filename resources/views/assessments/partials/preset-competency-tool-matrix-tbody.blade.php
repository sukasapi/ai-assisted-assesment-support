@foreach ($kelompokKompetensiMatriks as $group)
    <tr class="bg-zinc-100">
        <th scope="colgroup" class="sticky left-0 z-10 border-r border-b border-zinc-200 bg-zinc-100 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-zinc-700">
            {{ $group->nama }}
            <span class="ml-1 font-mono font-normal normal-case text-zinc-500">({{ $group->kode }})</span>
        </th>
        @foreach ($pemilihanAlatPreset as $sel)
            <td class="border-b border-zinc-200 bg-zinc-100"></td>
        @endforeach
    </tr>
    @foreach ($group->competencies as $c)
        <tr class="hover:bg-zinc-50/80">
            <th scope="row" class="sticky left-0 z-10 border-r border-zinc-200 bg-white px-3 py-2 text-left font-normal text-zinc-800">
                <span class="font-mono text-xs">{{ $c->kode_kompetensi }}</span>
                <span class="mt-0.5 block truncate text-xs text-zinc-500" title="{{ $c->nama }}">{{ $c->nama }}</span>
            </th>
            @foreach ($pemilihanAlatPreset as $sel)
                @php
                    $toolId = $sel->id_alat_penilaian;
                    $cellKey = $c->id.'-'.$toolId;
                    /** @var \App\Models\CompetencyToolMapping|null $map */
                    $map = $pemetaanKompetensiAlat->get($cellKey);
                @endphp
                <td class="px-1 py-1.5 text-center align-middle">
                    @if ($map !== null)
                        @if ($map->wajib)
                            <span class="inline-flex size-8 items-center justify-center rounded-full bg-rose-100 text-xs font-semibold text-rose-900" title="Terpakai di matriks (wajib)">✓</span>
                        @else
                            <span class="inline-flex size-8 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-800" title="Terpakai di matriks (opsional)">✓</span>
                        @endif
                    @else
                        <span class="text-zinc-300" title="Tidak dipetakan pada versi matriks ini">—</span>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
@endforeach
