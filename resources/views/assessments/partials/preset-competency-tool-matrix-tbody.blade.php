@foreach ($kelompokKompetensiMatriks as $group)
    <tr class="bg-surface-container">
        <th scope="colgroup" class="sticky left-0 z-10 border-r border-b border-outline-variant/40 bg-surface-container px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-on-surface-variant">
            {{ $group->nama }}
            <span class="ml-1 font-mono font-normal normal-case text-on-surface-variant">({{ $group->kode }})</span>
        </th>
        @foreach ($pemilihanAlatPreset as $sel)
            <td class="border-b border-outline-variant/40 bg-surface-container"></td>
        @endforeach
    </tr>
    @foreach ($group->competencies as $c)
        <tr class="hover:bg-surface-container-low/80">
            <th scope="row" class="sticky-col-header border-r border-outline-variant/40est px-3 py-2 text-left font-normal text-on-surface">
                <span class="font-mono text-xs">{{ $c->kode_kompetensi }}</span>
                <span class="mt-0.5 block truncate text-xs text-on-surface-variant" title="{{ $c->nama }}">{{ $c->nama }}</span>
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
                            <span class="inline-flex size-8 items-center justify-center rounded-full bg-surface-container-high text-xs font-medium text-on-surface" title="Terpakai di matriks (opsional)">✓</span>
                        @endif
                    @else
                        <span class="text-on-surface-variant/50" title="Tidak dipetakan pada versi matriks ini">—</span>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
@endforeach
