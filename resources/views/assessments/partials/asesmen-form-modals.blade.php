@php
    use App\Support\AssessmentFormOptions;

    $formOpts = AssessmentFormOptions::untukForm();
    $peserta = $formOpts['peserta'];
    $versiMatriks = $formOpts['versiMatriks'];
    $asesorKandidat = $formOpts['asesorKandidat'];
    $opsiTemplatePromptAi = $formOpts['opsiTemplatePromptAi'];
    $storeUrlTemplate = route('sesi-asesmen.asesmen.store', ['sesiAsesmen' => 999999999]);
    $updateUrlTemplate = route('asesmen.update', ['asesmen' => 999999999]);
@endphp

<dialog id="modal-asesmen-tambah" data-competency-modal data-modal-size="fullscreen" class="asesmen-form-modal">
    <form method="POST" action="#" class="asesmen-form-modal__form" id="form-asesmen-tambah">
        @csrf
        <input type="hidden" name="id_sesi_asesmen" value="">
        <input type="hidden" name="_sesi_label" value="">
        <header class="asesmen-form-modal__header">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide text-primary">Buat asesmen</p>
                <h2 class="truncate text-lg font-bold text-on-surface" data-sesi-label>Sesi assessment</h2>
            </div>
            <button type="button" data-close-modal="modal-asesmen-tambah" class="asesmen-form-modal__close" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="asesmen-form-modal__body">
            @include('assessments.partials.asesmen-form-body', [
                'formKey' => 'tambah',
                'peserta' => $peserta,
                'versiMatriks' => $versiMatriks,
                'asesorKandidat' => $asesorKandidat,
                'opsiTemplatePromptAi' => $opsiTemplatePromptAi,
                'selectedAsesorIds' => [],
                'selectedPesertaIds' => [],
                'values' => [],
                'multiPeserta' => true,
            ])
        </div>
        <footer class="asesmen-form-modal__footer">
            <button type="button" data-close-modal="modal-asesmen-tambah" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface">Batal</button>
            <button type="submit" class="asesmen-form-modal__submit">
                <span class="material-symbols-outlined text-lg">save</span>
                Simpan Asesmen
            </button>
        </footer>
    </form>
</dialog>

<dialog id="modal-asesmen-ubah" data-competency-modal data-modal-size="fullscreen" class="asesmen-form-modal">
    <form method="POST" action="#" class="asesmen-form-modal__form" id="form-asesmen-ubah">
        @csrf
        @method('PUT')
        <input type="hidden" name="_edit_id" value="{{ old('_edit_id') }}">
        <input type="hidden" name="_asesmen_label" value="{{ old('_asesmen_label') }}">
        <header class="asesmen-form-modal__header">
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-wide text-primary">Ubah asesmen</p>
                <h2 class="truncate text-lg font-bold text-on-surface" data-asesmen-label>Asesmen</h2>
            </div>
            <button type="button" data-close-modal="modal-asesmen-ubah" class="asesmen-form-modal__close" aria-label="Tutup">
                <span class="material-symbols-outlined">close</span>
            </button>
        </header>
        <div class="asesmen-form-modal__body">
            @include('assessments.partials.asesmen-form-body', [
                'formKey' => 'ubah',
                'peserta' => $peserta,
                'versiMatriks' => $versiMatriks,
                'asesorKandidat' => $asesorKandidat,
                'opsiTemplatePromptAi' => $opsiTemplatePromptAi,
                'selectedAsesorIds' => [],
                'values' => [],
            ])
        </div>
        <footer class="asesmen-form-modal__footer">
            <button type="button" data-close-modal="modal-asesmen-ubah" class="text-sm font-semibold text-on-surface-variant hover:text-on-surface">Batal</button>
            <button type="submit" class="asesmen-form-modal__submit">
                <span class="material-symbols-outlined text-lg">save</span>
                Simpan Asesmen
            </button>
        </footer>
    </form>
</dialog>

@include('assessments.partials.peserta-picker-modal', ['peserta' => $peserta])
@include('assessments.partials.asesor-picker-modal', ['asesorKandidat' => $asesorKandidat])

@include('assessments.partials.asesmen-form-script', [
    'storeUrlTemplate' => $storeUrlTemplate,
    'updateUrlTemplate' => $updateUrlTemplate,
])
