@extends('layouts.app')

@section('title', 'Buat asesmen — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Buat asesmen" :back-url="route('sesi-asesmen.show', $sesi)" back-label="Kembali ke sesi">
        <x-slot:description>
            Sesi: <strong>{{ $sesi->kode_sesi }}</strong> — {{ $sesi->nama }}.
            Pilih peserta, versi matriks, tujuan, dan admin penilai. Alat penilaian diisi otomatis dari preset.
        </x-slot:description>
    </x-ui.page-header>

    <form method="POST" action="{{ route('sesi-asesmen.asesmen.store', $sesi) }}" class="max-w-xl space-y-6">
        @csrf

        <section class="card-depth space-y-5 rounded-xl p-6">
            <h2 class="text-section-header uppercase text-on-surface-variant">Peserta &amp; matriks</h2>

            <x-ui.form-select label="Peserta" name="id_peserta" required>
                <option value="">— pilih —</option>
                @foreach ($peserta as $p)
                    <option value="{{ $p->id }}" @selected(old('id_peserta') == $p->id)>{{ $p->nama_lengkap }} ({{ $p->kode_peserta }})</option>
                @endforeach
            </x-ui.form-select>

            <x-ui.form-select label="Versi matriks" name="id_versi_matriks" required>
                <option value="">— pilih —</option>
                @foreach ($versiMatriks as $v)
                    <option value="{{ $v->id }}" @selected(old('id_versi_matriks') == $v->id)>{{ $v->kode_versi }} — {{ $v->nama_versi }}</option>
                @endforeach
            </x-ui.form-select>

            <x-ui.form-select label="Tujuan" name="tujuan" required>
                <option value="promosi" @selected(old('tujuan') === 'promosi')>Promosi</option>
                <option value="pemetaan_talenta" @selected(old('tujuan', 'pemetaan_talenta') === 'pemetaan_talenta')>Pemetaan talenta</option>
            </x-ui.form-select>
        </section>

        <section class="card-depth rounded-xl p-6">
            <fieldset>
                <legend class="text-section-header uppercase text-on-surface-variant">Cara mengumpulkan bukti</legend>
                <p class="mt-1 text-xs text-on-surface-variant">Menentukan formulir mana yang aktif di halaman detail asesmen (dapat diubah nanti).</p>
                <div class="mt-4 space-y-3">
                    <label class="flex cursor-pointer gap-3 rounded-lg border border-outline-variant bg-surface-container-low p-3 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                        <input type="radio" name="metode_koleksi_bukti" value="manual" class="mt-1 size-4 border-outline-variant text-primary focus:ring-primary/20" @checked(old('metode_koleksi_bukti', 'manual') === 'manual')>
                        <span>
                            <span class="block text-sm font-semibold text-on-surface">Manual — bukti per kompetensi</span>
                            <span class="mt-0.5 block text-xs text-on-surface-variant">Input bukti per alat dan kompetensi; analisis AI per baris bukti.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer gap-3 rounded-lg border border-outline-variant bg-surface-container-low p-3 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20">
                        <input type="radio" name="metode_koleksi_bukti" value="payload_alat" class="mt-1 size-4 border-outline-variant text-primary focus:ring-primary/20" @checked(old('metode_koleksi_bukti') === 'payload_alat')>
                        <span>
                            <span class="block text-sm font-semibold text-on-surface">Otomatis — payload alat + AI bulk</span>
                            <span class="mt-0.5 block text-xs text-on-surface-variant">Unggah teks muatan per alat; hasil wajib direview asesor.</span>
                        </span>
                    </label>
                </div>
            </fieldset>
        </section>

        <section class="card-depth space-y-5 rounded-xl p-6">
            <h2 class="text-section-header uppercase text-on-surface-variant">Pengaturan lainnya</h2>

            @if (($opsiTemplatePromptAi ?? []) !== [])
                <x-ui.form-select label="Template prompt AI — terapkan ke semua alat (opsional)" name="id_template_prompt_ai" hint="Menerapkan template yang sama ke tiap alat aktif. Di detail asesmen dapat diatur per alat atau ikuti default master (mis. STAR hanya BEI).">
                    <option value="">— tanpa template tambahan —</option>
                    @foreach ($opsiTemplatePromptAi as $tpl)
                        <option value="{{ $tpl['id'] }}" @selected((int) old('id_template_prompt_ai') === $tpl['id'])>
                            {{ $tpl['nama'] }} ({{ $tpl['kode'] }})
                        </option>
                    @endforeach
                </x-ui.form-select>
            @endif

            <div class="flex items-center gap-2">
                <input type="hidden" name="tanpa_intray" value="0">
                <input type="checkbox" name="tanpa_intray" id="tanpa_intray" value="1" class="size-4 rounded border-outline-variant text-primary focus:ring-primary/20" @checked(old('tanpa_intray'))>
                <label for="tanpa_intray" class="text-sm text-on-surface">Tanpa INTRAY (mis. BOD-3)</label>
            </div>

            <div>
                <span class="block text-sm font-semibold text-on-surface">Admin penilai (opsional)</span>
                <p class="mt-0.5 text-xs text-on-surface-variant">Hanya akun admin; pembuat asesmen otomatis ditugaskan.</p>
                <div class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-lg border border-outline-variant bg-surface-container-low p-3">
                    @foreach ($asesorKandidat as $u)
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="id_asesor[]" value="{{ $u->id }}" class="size-4 rounded border-outline-variant" @checked(collect(old('id_asesor', []))->contains($u->id))>
                            <span>{{ $u->name }} <span class="text-on-surface-variant">({{ $u->role }})</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex gap-3 border-t border-outline-variant/30 pt-4">
                <x-ui.button type="submit" variant="primary">Simpan</x-ui.button>
                <x-ui.button href="{{ route('asesmen.index') }}" variant="secondary">Batal</x-ui.button>
            </div>
        </section>
    </form>
@endsection
