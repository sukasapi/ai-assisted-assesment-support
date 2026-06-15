@php
    use App\Enums\EvidenceSourceType;
    use App\Enums\EvidenceTranscriptionStatus;

    $jenisBukti = $b->jenis_sumber ?? EvidenceSourceType::Teks;
    $statusTrx = $b->status_transkripsi;
    $tampilFormEdit = $bisaEdit && request()->query('edit_bukti') == $b->id;
    $kutipanAi = is_array($b->ai_muatan) ? ($b->ai_muatan['kutipan_dari_teks_mentah'] ?? '') : '';
@endphp

<div
    class="evidence-uploaded-card rounded-xl border border-outline-variant/30 bg-surface-container-lowest p-3 shadow-sm"
    data-evidence-row="{{ $b->id }}"
    data-kompetensi-id="{{ $b->id_kompetensi }}"
    data-alat-id="{{ $b->id_alat_penilaian }}"
    data-preview-tool="{{ $b->tool?->kode ?? '—' }}"
    data-preview-tool-nama="{{ $b->tool?->nama ?? '' }}"
    data-preview-kompetensi="{{ $b->competency?->kode_kompetensi }} — {{ $b->competency?->nama }}"
    data-preview-tanggal="{{ $b->dibuat_pada?->timezone(config('app.timezone'))->format('d M Y') ?? '—' }}"
    data-preview-jenis="{{ $jenisBukti->label() }}"
    data-preview-ai-tingkat="{{ $b->ai_tingkat ?? '' }}"
    data-preview-ai-alasan="{{ $b->ai_alasan ?? '' }}"
    data-preview-ai-kutipan="{{ $kutipanAi }}"
    @if ($b->path_audio) data-preview-audio-url="{{ route('asesmen.bukti.audio', [$asesmen, $b]) }}" @endif
>
    <template class="js-evidence-preview-teks">{{ $b->teks_mentah }}</template>

    <div class="flex items-start justify-between gap-2">
        <div class="min-w-0">
            <p class="text-[10px] font-bold uppercase tracking-wide text-on-surface-variant">
                {{ $b->tool?->kode ?? '—' }}
                · {{ $b->dibuat_pada?->timezone(config('app.timezone'))->format('d M Y') ?? '—' }}
            </p>
            <p class="mt-0.5 truncate text-xs font-semibold text-on-surface">{{ $b->competency?->kode_kompetensi }} — {{ $b->competency?->nama }}</p>
        </div>
        @if ($b->ai_tingkat)
            <span class="shrink-0 rounded-full bg-primary-fixed px-2 py-0.5 text-[10px] font-bold text-primary">Lv {{ $b->ai_tingkat }}</span>
        @endif
    </div>

    <p class="mt-2 line-clamp-3 text-xs leading-relaxed text-on-surface-variant">{{ \Illuminate\Support\Str::limit($b->teks_mentah, 160) }}</p>

    @if ($b->ai_alasan || $kutipanAi !== '')
        <p class="mt-2 text-[11px] italic text-primary/90">{{ \Illuminate\Support\Str::limit($b->ai_alasan ?: $kutipanAi, 100) }}</p>
    @endif

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="rounded-md border border-outline-variant/40 px-1.5 py-0.5 text-[10px] uppercase text-on-surface-variant">{{ $jenisBukti->label() }}</span>
        <button
            type="button"
            class="js-evidence-preview-open inline-flex items-center gap-0.5 text-[11px] font-bold text-primary hover:underline"
        >
            <span class="material-symbols-outlined text-sm">visibility</span>
            Preview
        </button>
        @if ($bisaEdit)
            <button type="button" class="js-evidence-edit-toggle text-[11px] font-bold text-primary hover:underline {{ $tampilFormEdit ? 'hidden' : '' }}" data-target="evidence-edit-{{ $b->id }}">
                Ubah
            </button>
            <form
                method="POST"
                action="{{ route('asesmen.bukti.destroy', [$asesmen, $b]) }}"
                class="inline"
                data-swal-confirm="Bukti akan dihapus (soft delete) dan tidak lagi tampil di daftar."
                data-swal-confirm-title="Hapus bukti?"
                data-swal-confirm-yes="Ya, hapus"
                data-swal-confirm-danger="1"
            >
                @csrf
                @method('DELETE')
                <input type="hidden" name="id_kompetensi" value="{{ $b->id_kompetensi }}">
                <input type="hidden" name="id_alat_penilaian" value="{{ $b->id_alat_penilaian }}">
                <button type="submit" class="inline-flex items-center gap-0.5 text-[11px] font-bold text-error hover:underline">
                    <span class="material-symbols-outlined text-sm">delete</span>
                    Hapus
                </button>
            </form>
            @if ($b->ai_alasan || $b->ai_tingkat)
                <form method="POST" action="{{ route('asesmen.bukti.mapping', [$asesmen, $b]) }}" class="inline">
                    @csrf
                    <button type="submit" class="text-[11px] font-bold text-on-surface-variant hover:text-primary">Mapping</button>
                </form>
            @endif
        @endif
    </div>

    @if ($bisaEdit)
        <form
            id="evidence-edit-{{ $b->id }}"
            method="POST"
            action="{{ route('asesmen.bukti.update', [$asesmen, $b]) }}"
            enctype="multipart/form-data"
            class="js-evidence-form mt-3 space-y-3 {{ $tampilFormEdit ? '' : 'hidden' }}"
            data-evidence-edit-form
            data-has-audio="{{ $b->path_audio ? '1' : '0' }}"
            data-transcript-preview-url="{{ route('asesmen.bukti.transkrip.preview', $asesmen) }}"
            data-transcript-evidence-url="{{ route('asesmen.bukti.transkrip', [$asesmen, $b]) }}"
            @if ($b->path_audio) data-audio-play-url="{{ route('asesmen.bukti.audio', [$asesmen, $b]) }}" @endif
        >
            @csrf
            @method('PATCH')
            <input type="hidden" name="id_kompetensi" value="{{ $b->id_kompetensi }}">
            <input type="hidden" name="id_alat_penilaian" value="{{ $b->id_alat_penilaian }}">
            <div class="flex flex-wrap gap-3 text-xs">
                <label class="inline-flex items-center gap-1"><input type="radio" name="jenis_sumber" value="teks" class="js-evidence-jenis text-primary" @checked($jenisBukti === EvidenceSourceType::Teks)> Teks</label>
                <label class="inline-flex items-center gap-1"><input type="radio" name="jenis_sumber" value="wawancara" class="js-evidence-jenis text-primary" @checked($jenisBukti === EvidenceSourceType::Wawancara)> Wawancara</label>
            </div>
            <div class="js-evidence-field-wawancara space-y-2 {{ $jenisBukti === EvidenceSourceType::Teks ? 'hidden' : '' }}">
                <input type="file" name="berkas_audio" accept="audio/*" class="w-full text-xs file:mr-2 file:rounded file:border-0 file:bg-primary-fixed file:px-2 file:py-1 file:text-primary">
                <div class="flex gap-2">
                    @if (config('stt.aktif'))
                        <button type="button" class="js-evidence-transcript rounded border border-primary/30 bg-primary-fixed px-2 py-1 text-xs font-bold text-primary">Transcript</button>
                    @endif
                    <button type="button" class="js-evidence-play rounded border px-2 py-1 text-xs {{ $b->path_audio ? '' : 'hidden' }}">Play</button>
                </div>
            </div>
            <div class="js-evidence-teks-block">
                <textarea name="teks_mentah" rows="4" class="w-full rounded-lg border border-outline-variant/40 px-2 py-1.5 text-xs">{{ $b->teks_mentah }}</textarea>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg accent-gradient px-3 py-1.5 text-xs font-bold text-white">Simpan</button>
                <button type="button" class="js-evidence-edit-cancel rounded-lg border px-3 py-1.5 text-xs text-on-surface-variant" data-view="evidence-view-{{ $b->id }}" data-form="evidence-edit-{{ $b->id }}">Batal</button>
            </div>
        </form>
    @endif
</div>
