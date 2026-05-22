<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Enums\AssessmentPurpose;
use App\Enums\AssessmentStatus;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\StoreAssessmentToolPayloadRequest;
use App\Http\Requests\StoreEvidenceRequest;
use App\Http\Requests\StoreKeyBehaviorRequest;
use App\Enums\AssessmentToolPromptMode;
use App\Http\Requests\UpdateAssessmentAiPromptTemplateRequest;
use App\Http\Requests\UpdateAssessmentToolAiPromptRequest;
use App\Http\Requests\UpdateAssessmentEvidenceCollectionModeRequest;
use App\Http\Requests\UpdateKeyBehaviorRequest;
use App\Models\Assessment;
use App\Models\AssessmentAssessor;
use App\Models\AssessmentToolPayload;
use App\Models\AssessmentToolSelection;
use App\Models\Competency;
use App\Models\CompetencyGroup;
use App\Models\CompetencyLevel;
use App\Models\CompetencyToolMapping;
use App\Models\Evidence;
use App\Models\KeyBehavior;
use App\Models\MatrixVersion;
use App\Models\Participant;
use App\Models\User;
use App\Services\Ai\AiAnalysisDispatcher;
use App\Support\AiModelCatalog;
use App\Support\AiPromptComposer;
use App\Support\AiPromptTemplateResolver;
use App\Support\KeyBehaviorPresentation;
use App\Support\PayloadAnalysisPresenter;
use App\Support\ToolPayloadDeletionGuard;
use App\Services\Ai\EvidenceAiAnalyzer;
use App\Services\Assessment\AlatAsesmenPreset;
use App\Services\Assessment\AssessmentToolAvailabilityDiagnostic;
use App\Services\Integration\CompetencyIntegrationService;
use App\Support\MandatoryCompetencyCoverage;
use App\Support\AiFeature;
use App\Support\CatatAktivitas;
use App\Support\BulkTextNormalizer;
use App\Support\EvidenceTextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Assessment::class);

        $daftar = Assessment::query()
            ->with(['participant', 'matrixVersion'])
            ->latest('dibuat_pada')
            ->paginate(15);

        return view('assessments.index', ['daftar' => $daftar]);
    }

    public function create(): View
    {
        $this->authorize('create', Assessment::class);

        return view('assessments.create', [
            'peserta' => Participant::query()->where('aktif', true)->orderBy('nama_lengkap')->get(),
            'versiMatriks' => MatrixVersion::query()->where('aktif', true)->orderBy('nama_versi')->get(),
            'asesorKandidat' => User::query()
                ->whereIn('peran', ['admin', 'konsultan'])
                ->orderBy('nama')
                ->get(),
            'opsiTemplatePromptAi' => AiPromptComposer::opsiTemplateAktif(),
        ]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $tujuan = AssessmentPurpose::from($request->string('tujuan')->toString());
        $metode = AssessmentEvidenceCollectionMode::from($request->string('metode_koleksi_bukti')->toString());

        $asesmen = DB::transaction(function () use ($request, $tujuan, $metode): Assessment {
            /** @var Assessment $row */
            $idTemplate = $request->filled('id_template_prompt_ai')
                ? $request->integer('id_template_prompt_ai')
                : null;

            $row = Assessment::query()->create([
                'id_peserta' => $request->integer('id_peserta'),
                'id_versi_matriks' => $request->integer('id_versi_matriks'),
                'tujuan' => $tujuan,
                'status' => AssessmentStatus::Draf,
                'tanpa_intray' => $request->boolean('tanpa_intray'),
                'metode_koleksi_bukti' => $metode,
                'id_template_prompt_ai' => $idTemplate,
                'id_pengguna_pembuat' => $request->user()?->id,
            ]);

            $idsAsesor = array_unique($request->input('id_asesor', []));
            foreach ($idsAsesor as $idPengguna) {
                AssessmentAssessor::query()->create([
                    'id_asesmen' => $row->id,
                    'id_pengguna' => (int) $idPengguna,
                ]);
            }

            AlatAsesmenPreset::buatPemilihan(
                $row->id,
                $row->id_versi_matriks,
                $tujuan,
                $row->tanpa_intray
            );

            AiPromptTemplateResolver::inisialisasiAlatAktifBilaPerlu($row);
            if ($idTemplate !== null) {
                AiPromptTemplateResolver::terapkanKeSemuaAlatAktif($row, $idTemplate);
            }

            return $row;
        });

        CatatAktivitas::catat(
            $request->user(),
            'asesmen.dibuat',
            Assessment::class,
            $asesmen->id,
            [
                'id_peserta' => $asesmen->id_peserta,
                'id_versi_matriks' => $asesmen->id_versi_matriks,
                'tujuan' => $asesmen->tujuan->value,
                'tanpa_intray' => $asesmen->tanpa_intray,
                'metode_koleksi_bukti' => $asesmen->metode_koleksi_bukti->value,
            ],
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Asesmen berhasil dibuat.');
    }

    public function show(Assessment $asesmen): View
    {
        $this->authorize('view', $asesmen);

        $asesmen->load([
            'participant',
            'matrixVersion',
            'aiPromptTemplate',
            'assessorAssignments.user',
            'toolSelections.tool',
            'evidenceItems.tool',
            'evidenceItems.competency',
            'keyBehaviors.tool',
            'keyBehaviors.competency.group',
            'keyBehaviors.evidence',
            'keyBehaviors.competencyLevel',
            'toolPayloads.tool',
            'toolPayloads.uploader',
        ]);

        $kompetensi = Competency::query()->where('aktif', true)->orderBy('kode_kompetensi')->get();
        $tingkatKompetensi = CompetencyLevel::query()
            ->with('competency')
            ->orderBy('id_kompetensi')
            ->orderBy('tingkat')
            ->get();

        $urutanKelompok = ['INT' => 0, 'MNJ' => 1, 'LDR' => 2];
        $kelompokKompetensiMatriks = CompetencyGroup::query()
            ->whereNull('dihapus_pada')
            ->with(['competencies' => function ($query): void {
                $query->whereNull('dihapus_pada')->where('aktif', true)->orderBy('kode_kompetensi');
            }])
            ->get()
            ->sortBy(fn (CompetencyGroup $g) => $urutanKelompok[$g->kode] ?? 99)
            ->values()
            ->filter(fn (CompetencyGroup $g) => $g->competencies->isNotEmpty());

        $pemilihanAlatPreset = $asesmen->toolSelections
            ->sortBy(function ($s): array {
                $t = $s->tool;

                return [(int) ($t->urutan ?? 9999), $t->kode ?? ''];
            })
            ->values();

        $ketersediaanAlat = AssessmentToolAvailabilityDiagnostic::collectionsForShow($asesmen);
        $pemetaanKompetensiAlat = $ketersediaanAlat['pemetaanKompetensiAlat'];
        $alatTersediaInput = $ketersediaanAlat['alatTersediaInput'];

        $punyaKompetensiUntukMatriks = Competency::query()->whereNull('dihapus_pada')->exists();
        $ringkasanFinalisasi = MandatoryCompetencyCoverage::ringkasan($asesmen);
        $gridCakupanKompetensi = $this->gridCakupanKompetensi($asesmen, $ringkasanFinalisasi);
        $integrasiPratinjau = $asesmen->competencyIntegrations()
            ->with('competency')
            ->get()
            ->sortBy(fn ($row) => $row->competency?->kode_kompetensi ?? '')
            ->values();
        $ringkasanAlatPreset = $this->ringkasanAlatPreset($asesmen, $pemilihanAlatPreset, $pemetaanKompetensiAlat);
        $buktiPerAlat = $asesmen->evidenceItems->groupBy('id_alat_penilaian');
        $persenProgress = $ringkasanFinalisasi['total_wajib'] > 0
            ? (int) round(($ringkasanFinalisasi['total_terpenuhi'] / $ringkasanFinalisasi['total_wajib']) * 100)
            : 0;

        $idPerilakuDariBulkAi = KeyBehaviorPresentation::idDariAnalisisBulk($asesmen);

        return view('assessments.show', [
            'asesmen' => $asesmen,
            'idPerilakuDariBulkAi' => $idPerilakuDariBulkAi,
            'kompetensi' => $kompetensi,
            'tingkatKompetensi' => $tingkatKompetensi,
            'kelompokKompetensiMatriks' => $kelompokKompetensiMatriks,
            'pemilihanAlatPreset' => $pemilihanAlatPreset,
            'alatTersediaInput' => $alatTersediaInput,
            'pemetaanKompetensiAlat' => $pemetaanKompetensiAlat,
            'punyaKompetensiUntukMatriks' => $punyaKompetensiUntukMatriks,
            'ringkasanFinalisasi' => $ringkasanFinalisasi,
            'gridCakupanKompetensi' => $gridCakupanKompetensi,
            'ringkasanAlatPreset' => $ringkasanAlatPreset,
            'buktiPerAlat' => $buktiPerAlat,
            'persenProgress' => $persenProgress,
            'aiFiturAktif' => AiFeature::aktif(),
            'aiPesanNonaktif' => AiFeature::pesanNonaktif(),
            'aiModelOptions' => AiModelCatalog::daftarModel(),
            'aiModelDefault' => AiModelCatalog::modelDefault(),
            'aiAntrianAsync' => AiAnalysisDispatcher::antrianAsyncAktif(),
            'integrasiPratinjau' => $integrasiPratinjau,
            'opsiTemplatePromptAi' => AiPromptComposer::opsiTemplateAktif(),
            'ringkasanTemplatePerAlat' => AiPromptTemplateResolver::ringkasanPerAlatAktif($asesmen),
        ]);
    }

    public function updateAiPromptTemplate(UpdateAssessmentAiPromptTemplateRequest $request, Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        $idBaru = $request->filled('id_template_prompt_ai')
            ? $request->integer('id_template_prompt_ai')
            : null;

        if ((int) $asesmen->id_template_prompt_ai === (int) $idBaru) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->with('status', 'Template prompt AI tidak berubah.');
        }

        $asesmen->update(['id_template_prompt_ai' => $idBaru]);
        AiPromptTemplateResolver::terapkanKeSemuaAlatAktif($asesmen, $idBaru);

        CatatAktivitas::catat(
            $request->user(),
            'asesmen.template_prompt_ai.diubah',
            Assessment::class,
            $asesmen->id,
            ['id_template_prompt_ai' => $idBaru, 'diterapkan_ke_semua_alat' => true],
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Template diterapkan ke semua alat aktif. Sesuaikan per alat di bawah jika perlu.');
    }

    public function updateToolAiPrompts(UpdateAssessmentToolAiPromptRequest $request, Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        foreach ($request->input('prompt_alat', []) as $baris) {
            if (! is_array($baris)) {
                continue;
            }
            $idAlat = (int) ($baris['id_alat_penilaian'] ?? 0);
            if ($idAlat <= 0) {
                continue;
            }
            $mode = AssessmentToolPromptMode::tryFrom((string) ($baris['mode'] ?? ''))
                ?? AssessmentToolPromptMode::Master;
            $idTemplate = isset($baris['id_template_prompt_ai']) && $baris['id_template_prompt_ai'] !== ''
                ? (int) $baris['id_template_prompt_ai']
                : null;

            AiPromptTemplateResolver::simpanOverride($asesmen->id, $idAlat, $mode, $idTemplate);
        }

        CatatAktivitas::catat(
            $request->user(),
            'asesmen.template_prompt_alat.diubah',
            Assessment::class,
            $asesmen->id,
            ['jumlah_alat' => count($request->input('prompt_alat', []))],
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Template prompt per alat diperbarui.');
    }

    public function hitungIntegrasiPratinjau(Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        $hasil = app(CompetencyIntegrationService::class)->hitungUlang(
            $asesmen,
            request()->user()?->id,
        );

        if (! ($hasil['berhasil'] ?? false)) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['integrasi' => $hasil['pesan'] ?? 'Pratinjau integrasi gagal dihitung.']);
        }

        CatatAktivitas::catat(
            request()->user(),
            'asesmen.integrasi_dihitung',
            Assessment::class,
            $asesmen->id,
            [
                'jumlah_kompetensi' => $hasil['jumlah_kompetensi'] ?? 0,
                'job_fit_persen' => $hasil['job_fit_persen'] ?? null,
                'rekomendasi_agregat' => $hasil['rekomendasi_agregat'] ?? null,
            ],
        );

        $pesan = $hasil['pesan'] ?? 'Pratinjau integrasi diperbarui.';
        if (($hasil['jumlah_kompetensi'] ?? 0) > 0) {
            $jobFit = $hasil['job_fit_persen'] ?? null;
            $pesan = 'Pratinjau integrasi diperbarui untuk '.$hasil['jumlah_kompetensi'].' kompetensi.'
                .($jobFit !== null ? ' Job Fit pratinjau: '.number_format((float) $jobFit, 1).' %.' : '');
        }

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', $pesan);
    }

    public function toolDiagnostic(Assessment $asesmen): JsonResponse
    {
        $this->authorize('view', $asesmen);

        return response()->json(
            AssessmentToolAvailabilityDiagnostic::for($asesmen),
            200,
            [],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function updateEvidenceCollectionMode(UpdateAssessmentEvidenceCollectionModeRequest $request, Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        $baru = AssessmentEvidenceCollectionMode::from($request->string('metode_koleksi_bukti')->toString());
        $lama = $asesmen->metode_koleksi_bukti;
        if ($lama === $baru) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->with('status', 'Metode koleksi bukti tidak berubah.');
        }

        $punyaPayload = $asesmen->toolPayloads()->exists();
        $punyaPerilaku = $asesmen->keyBehaviors()->exists();

        $asesmen->update(['metode_koleksi_bukti' => $baru]);

        CatatAktivitas::catat(
            $request->user(),
            'asesmen.metode_koleksi_bukti.diubah',
            Assessment::class,
            $asesmen->id,
            [
                'dari' => $lama->value,
                'ke' => $baru->value,
                'punya_payload' => $punyaPayload,
                'punya_perilaku_kunci' => $punyaPerilaku,
            ],
        );

        $pesan = 'Metode koleksi bukti diperbarui.';
        if ($punyaPayload || $punyaPerilaku) {
            $pesan .= ' Mapping perilaku kunci dan indikator yang sudah dimasukkan tetap tersimpan; tampilan koleksi bukti menyesuaikan metode baru.';
        }

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', $pesan);
    }

    public function storeEvidence(StoreEvidenceRequest $request, Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        $teksMentah = $request->string('teks_mentah')->toString();
        $teksRich = $request->filled('teks_mentah_rich') ? $request->string('teks_mentah_rich')->toString() : null;
        $teksNormalized = EvidenceTextNormalizer::normalize(
            $teksRich,
            $request->input('teks_mentah_normalized', $teksMentah)
        );

        $asesmen->evidenceItems()->create([
            'id_alat_penilaian' => $request->integer('id_alat_penilaian'),
            'id_kompetensi' => $request->integer('id_kompetensi'),
            'teks_mentah' => $teksNormalized !== '' ? $teksNormalized : $teksMentah,
            'teks_mentah_rich' => $teksRich,
            'teks_mentah_normalized' => $teksNormalized !== '' ? $teksNormalized : $teksMentah,
            'teks_kerja' => $request->input('teks_kerja'),
        ]);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Bukti penilaian ditambahkan.');
    }

    public function storeKeyBehavior(StoreKeyBehaviorRequest $request, Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        $idBukti = $request->input('id_bukti_penilaian');

        $idTingkat = $request->input('id_tingkat_kompetensi') !== null && $request->input('id_tingkat_kompetensi') !== ''
            ? (int) $request->input('id_tingkat_kompetensi')
            : null;
        $teksPk = CompetencyLevel::teksIndikatorResmi($idTingkat)
            ?? $request->string('teks_perilaku')->toString();

        $asesmen->keyBehaviors()->create([
            'id_alat_penilaian' => $request->integer('id_alat_penilaian'),
            'id_kompetensi' => $request->integer('id_kompetensi'),
            'id_bukti_penilaian' => $idBukti !== null && $idBukti !== '' ? (int) $idBukti : null,
            'id_tingkat_kompetensi' => $idTingkat,
            'teks_perilaku' => $teksPk,
            'alasan_pemilihan' => $request->filled('alasan_pemilihan')
                ? $request->string('alasan_pemilihan')->toString()
                : null,
            'kutipan_referensi' => $request->filled('kutipan_referensi')
                ? $request->string('kutipan_referensi')->toString()
                : null,
            'tervalidasi' => true,
            'id_pengguna_validasi' => $request->user()?->id,
            'waktu_validasi' => now(),
        ]);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Perilaku kunci ditambahkan.');
    }

    public function editKeyBehavior(Assessment $asesmen, KeyBehavior $perilaku): View
    {
        $this->authorize('update', $asesmen);
        abort_unless((int) $perilaku->id_asesmen === (int) $asesmen->id, 404);

        $perilaku->load(['tool', 'competency', 'competencyLevel', 'evidence']);

        $tingkatUntukKompetensi = CompetencyLevel::query()
            ->where('id_kompetensi', $perilaku->id_kompetensi)
            ->whereNull('dihapus_pada')
            ->orderBy('tingkat')
            ->get();

        $teksIndikatorResmi = CompetencyLevel::teksIndikatorResmi($perilaku->id_tingkat_kompetensi) ?? '';

        return view('assessments.key-behaviors.edit', [
            'asesmen' => $asesmen,
            'perilaku' => $perilaku,
            'tingkatUntukKompetensi' => $tingkatUntukKompetensi,
            'teksIndikatorResmi' => $teksIndikatorResmi,
        ]);
    }

    public function updateKeyBehavior(UpdateKeyBehaviorRequest $request, Assessment $asesmen, KeyBehavior $perilaku): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless((int) $perilaku->id_asesmen === (int) $asesmen->id, 404);

        $data = [];

        if ($request->has('id_tingkat_kompetensi')) {
            $tingkatBaru = $request->filled('id_tingkat_kompetensi')
                ? $request->integer('id_tingkat_kompetensi')
                : null;
            $tingkatLama = $perilaku->id_tingkat_kompetensi;
            $data['id_tingkat_kompetensi'] = $tingkatBaru;

            if ($tingkatBaru !== null && (int) $tingkatBaru !== (int) ($tingkatLama ?? 0)) {
                $data['teks_perilaku'] = CompetencyLevel::teksIndikatorResmi($tingkatBaru) ?? $perilaku->teks_perilaku;
            }
        }

        if ($request->has('alasan_pemilihan')) {
            $data['alasan_pemilihan'] = $request->filled('alasan_pemilihan')
                ? $request->string('alasan_pemilihan')->toString()
                : null;
        }

        if ($request->boolean('simpan_sebagai_mapping')) {
            if ($perilaku->id_tingkat_kompetensi === null && ! isset($data['id_tingkat_kompetensi'])) {
                return redirect()
                    ->route('asesmen.show', $asesmen)
                    ->withErrors([
                        'perilaku_kunci' => 'Pilih tingkat indikator perilaku sebelum menyimpan sebagai mapping resmi.',
                    ]);
            }

            $data['tervalidasi'] = true;
            $data['id_pengguna_validasi'] = $request->user()?->id;
            $data['waktu_validasi'] = now();
        }

        if ($data !== []) {
            $perilaku->update($data);
        }

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Perilaku kunci diperbarui.');
    }

    public function sahkanKeyBehavior(Request $request, Assessment $asesmen, KeyBehavior $perilaku): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless((int) $perilaku->id_asesmen === (int) $asesmen->id, 404);

        $expectsJson = $request->expectsJson();

        $jsonError = fn (string $message, int $status = 422): JsonResponse => response()->json([
            'success' => false,
            'message' => $message,
        ], $status);

        $jsonSuccess = fn (KeyBehavior $row, string $message): JsonResponse => response()->json(array_merge([
            'success' => true,
            'message' => $message,
        ], $this->payloadBadgeStatusPk($row)));

        if ($asesmen->status === AssessmentStatus::SelesaiFinal) {
            return $expectsJson
                ? $jsonError('Asesmen sudah difinalisasi.')
                : redirect()
                    ->route('asesmen.show', $asesmen)
                    ->withErrors(['perilaku_kunci' => 'Asesmen sudah difinalisasi.']);
        }

        if ($perilaku->tervalidasi) {
            $perilaku->refresh();

            return $expectsJson
                ? $jsonSuccess($perilaku, 'Mapping sudah disahkan sebelumnya.')
                : redirect()
                    ->route('asesmen.show', $asesmen)
                    ->with('status', 'Mapping sudah disahkan sebelumnya.');
        }

        if ($perilaku->id_tingkat_kompetensi === null) {
            $pesan = 'Isi tingkat indikator perilaku (lewat Edit) sebelum menyimpan sebagai mapping resmi.';

            return $expectsJson
                ? $jsonError($pesan)
                : redirect()
                    ->route('asesmen.show', $asesmen)
                    ->withErrors(['perilaku_kunci' => $pesan]);
        }

        $perilaku->update([
            'tervalidasi' => true,
            'id_pengguna_validasi' => $request->user()?->id,
            'waktu_validasi' => now(),
        ]);

        CatatAktivitas::catat(
            $request->user(),
            'asesmen.perilaku_kunci.disahkan',
            KeyBehavior::class,
            $perilaku->id,
            [
                'id_asesmen' => $asesmen->id,
                'id_kompetensi' => $perilaku->id_kompetensi,
            ],
        );

        $perilaku->refresh();
        $pesan = 'Mapping perilaku kunci disahkan sebagai resmi.';

        return $expectsJson
            ? $jsonSuccess($perilaku, $pesan)
            : redirect()
                ->route('asesmen.show', $asesmen)
                ->with('status', $pesan);
    }

    /**
     * @return array{status_label: string, status_kelas: string}
     */
    private function payloadBadgeStatusPk(KeyBehavior $perilaku): array
    {
        $badge = KeyBehaviorPresentation::badgeStatus($perilaku);

        return [
            'status_label' => $badge['label'],
            'status_kelas' => $badge['kelas'],
        ];
    }

    public function analyzeEvidenceAi(Assessment $asesmen, Evidence $bukti): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless((int) $bukti->id_asesmen === (int) $asesmen->id, 404);

        if ($asesmen->metode_koleksi_bukti !== AssessmentEvidenceCollectionMode::Manual) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => 'Analisis AI per bukti hanya untuk metode manual. Ubah metode koleksi bukti atau gunakan analisis bulk pada payload.']);
        }
        if (! config('ai.aktif')) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => 'Fitur AI tidak aktif (AI_AKTIF=false).']);
        }

        $analisis = app(EvidenceAiAnalyzer::class)->analisisInkremental($bukti, request()->user());

        if (! $analisis['berhasil']) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => $analisis['pesan'] ?? 'Analisis AI gagal.']);
        }

        CatatAktivitas::catat(
            request()->user(),
            'bukti.ai_dianalisis',
            Evidence::class,
            $bukti->id,
            ['id_asesmen' => $asesmen->id],
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Analisis AI untuk bukti #'.$bukti->id.' selesai.');
    }

    public function storeToolPayload(StoreAssessmentToolPayloadRequest $request, Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        $teksMuatan = $request->string('teks_muatan')->toString();
        $teksMuatanRich = $request->filled('teks_muatan_rich') ? $request->string('teks_muatan_rich')->toString() : null;
        $teksKanonic = BulkTextNormalizer::canonicalPayloadMuatan(
            $teksMuatanRich,
            $request->input('teks_muatan_normalized'),
            $teksMuatan,
        );

        $asesmen->toolPayloads()->create([
            'id_alat_penilaian' => $request->integer('id_alat_penilaian'),
            'teks_muatan' => $teksKanonic,
            'teks_muatan_rich' => $teksMuatanRich,
            'teks_muatan_normalized' => $teksKanonic,
            'id_pengguna_pengunggah' => $request->user()?->id,
        ]);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Payload alat disimpan. Anda dapat menjalankan analisis AI bulk.');
    }

    public function destroyToolPayload(Assessment $asesmen, AssessmentToolPayload $payload): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless((int) $payload->id_asesmen === (int) $asesmen->id, 404);

        $alasan = ToolPayloadDeletionGuard::alasanTidakDapatDihapus($payload);
        if ($alasan !== null) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['payload' => $alasan]);
        }

        $idPayload = $payload->id;

        DB::transaction(function () use ($payload): void {
            ToolPayloadDeletionGuard::hapusPerilakuKunciDraft($payload);
            $payload->delete();
        });

        CatatAktivitas::catat(
            request()->user(),
            'payload_alat.dihapus',
            AssessmentToolPayload::class,
            $idPayload,
            ['id_asesmen' => $asesmen->id],
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Payload #'.$idPayload.' dihapus.');
    }

    public function showToolPayload(Assessment $asesmen, AssessmentToolPayload $payload): JsonResponse
    {
        $this->authorize('view', $asesmen);
        abort_unless((int) $payload->id_asesmen === (int) $asesmen->id, 404);

        $payload->load(['tool', 'uploader']);

        return response()->json(
            PayloadAnalysisPresenter::detail($payload),
            200,
            [],
            JSON_UNESCAPED_UNICODE,
        );
    }

    public function toolPayloadStatuses(Assessment $asesmen): JsonResponse
    {
        $this->authorize('view', $asesmen);

        $payloads = $asesmen->toolPayloads()->with('tool')->get();
        $data = [];
        foreach ($payloads as $p) {
            $data[(string) $p->id] = PayloadAnalysisPresenter::ringkasan($p);
        }

        return response()->json([
            'payloads' => $data,
            'ada_yang_berjalan' => collect($data)->contains(fn (array $r): bool => $r['sedang_berjalan']),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function redirectToolPayloadAiGet(Assessment $asesmen): RedirectResponse
    {
        $this->authorize('view', $asesmen);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->withErrors([
                'ai' => 'Analisis AI bulk harus dipicu dari tombol pada halaman asesmen (bukan membuka URL ini langsung di browser).',
            ]);
    }

    public function analyzeToolPayloadAi(Assessment $asesmen, AssessmentToolPayload $payload): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        if ((int) $payload->id_asesmen !== (int) $asesmen->id) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => 'Payload tidak termasuk asesmen ini.']);
        }

        if ($asesmen->metode_koleksi_bukti !== AssessmentEvidenceCollectionMode::PayloadAlat) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => 'Analisis AI bulk hanya untuk metode otomatis (payload alat). Ubah metode koleksi bukti di atas.']);
        }
        if (! config('ai.aktif')) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => 'Fitur AI tidak aktif (AI_AKTIF=false).']);
        }

        $namaModel = request()->input('nama_model');
        $hasil = app(AiAnalysisDispatcher::class)->analisisPayloadBulk(
            $payload,
            request()->user(),
            is_string($namaModel) ? $namaModel : null,
        );

        if (! ($hasil['berhasil'] ?? false)) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors(['ai' => $hasil['pesan'] ?? 'Analisis AI bulk gagal.']);
        }

        if ($hasil['diantrian'] ?? false) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->with('status', $hasil['pesan'] ?? 'Analisis AI bulk dijadwalkan.');
        }

        CatatAktivitas::catat(
            request()->user(),
            'payload_alat.ai_diproses',
            AssessmentToolPayload::class,
            $payload->id,
            [
                'id_asesmen' => $asesmen->id,
                'jumlah_perilaku_kunci' => $hasil['jumlah_perilaku_kunci'] ?? 0,
            ],
        );

        $n = (int) ($hasil['jumlah_perilaku_kunci'] ?? 0);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Analisis AI untuk payload #'.$payload->id.' selesai. Perilaku kunci otomatis dibuat: '.$n.' entri.');
    }

    public function finalize(Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);

        if ($asesmen->status === AssessmentStatus::SelesaiFinal) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->with('status', 'Asesmen sudah difinalisasi sebelumnya.');
        }

        $ringkasan = MandatoryCompetencyCoverage::ringkasan($asesmen);
        if ($ringkasan['total_kompetensi_kurang'] > 0) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors([
                    'finalisasi' => 'Finalisasi belum bisa dilakukan. Setiap kompetensi wajib harus punya minimal satu perilaku kunci disahkan (mapping resmi) dengan tingkat indikator terisi.',
                ]);
        }

        $asesmen->update([
            'status' => AssessmentStatus::SelesaiFinal,
            'id_pengguna_finalisasi' => request()->user()?->id,
            'waktu_finalisasi' => now(),
        ]);

        CatatAktivitas::catat(
            request()->user(),
            'asesmen.difinalisasi',
            Assessment::class,
            $asesmen->id,
            ['status' => AssessmentStatus::SelesaiFinal->value]
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Asesmen berhasil difinalisasi.');
    }

    public function unfinalize(Assessment $asesmen): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless((request()->user()?->peran ?? '') === 'admin', 403);

        if ($asesmen->status !== AssessmentStatus::SelesaiFinal) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->with('status', 'Asesmen belum berstatus final.');
        }

        $asesmen->update([
            'status' => AssessmentStatus::Draf,
            'id_pengguna_finalisasi' => null,
            'waktu_finalisasi' => null,
        ]);

        CatatAktivitas::catat(
            request()->user(),
            'asesmen.finalisasi_dibatalkan',
            Assessment::class,
            $asesmen->id,
            ['status' => AssessmentStatus::Draf->value]
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Finalisasi asesmen dibatalkan. Status kembali ke draf.');
    }

    /**
     * @return array{
     *   total_wajib:int,
     *   total_terpenuhi:int,
     *   total_kompetensi_kurang:int,
     *   kompetensi_kurang: array<int, array{kode:string, nama:string}>
     * }
     */
    /**
     * @return list<array{nama: string, kode: string, persen: int}>
     */
    private function gridCakupanKompetensi(Assessment $asesmen, array $ringkasan): array
    {
        if ($ringkasan['total_wajib'] === 0) {
            return [];
        }

        $alatAktif = AssessmentToolSelection::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('aktif', true)
            ->pluck('id_alat_penilaian')
            ->all();

        $mapWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $alatAktif)
            ->where(function ($query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->where('wajib', true)
            ->with('competency')
            ->get();

        $kompetensiWajib = $mapWajib
            ->pluck('competency')
            ->filter()
            ->unique('id')
            ->sortBy('kode_kompetensi')
            ->values();

        $idTerpenuhi = MandatoryCompetencyCoverage::queryPkLayak($asesmen)
            ->pluck('id_kompetensi')
            ->flip();

        $idAdaPkDraft = KeyBehavior::query()
            ->where('id_asesmen', $asesmen->id)
            ->where(function ($q): void {
                $q->where('tervalidasi', false)
                    ->orWhereNull('id_tingkat_kompetensi');
            })
            ->pluck('id_kompetensi')
            ->flip();

        $grid = [];
        foreach ($kompetensiWajib as $c) {
            $terpenuhi = $idTerpenuhi->has($c->id);
            $adaDraft = $idAdaPkDraft->has($c->id);
            $persen = $terpenuhi ? 100 : ($adaDraft ? 40 : 0);
            $grid[] = [
                'nama' => (string) $c->nama,
                'kode' => (string) $c->kode_kompetensi,
                'persen' => $persen,
                'terpenuhi' => $terpenuhi,
                'ada_pk' => $adaDraft,
            ];
        }

        return $grid;
    }

    /**
     * @return list<array{
     *   kode: string,
     *   nama: string,
     *   kompetensi_label: string,
     *   status: string,
     *   status_kelas: string,
     * }>
     */
    private function ringkasanAlatPreset(Assessment $asesmen, $pemilihanAlatPreset, $pemetaanKompetensiAlat): array
    {
        $rows = [];
        $pkByKompetensi = $asesmen->keyBehaviors
            ->whereNotNull('id_tingkat_kompetensi')
            ->pluck('id_kompetensi')
            ->unique()
            ->flip();

        foreach ($pemilihanAlatPreset->where('aktif', true) as $sel) {
            $tool = $sel->tool;
            if ($tool === null) {
                continue;
            }

            $idAlat = (int) $sel->id_alat_penilaian;
            $kompetensiIds = $pemetaanKompetensiAlat
                ->filter(fn (CompetencyToolMapping $m): bool => (int) $m->id_alat_penilaian === $idAlat)
                ->pluck('id_kompetensi')
                ->unique();

            $kompetensi = Competency::query()
                ->whereIn('id', $kompetensiIds->all())
                ->orderBy('kode_kompetensi')
                ->get();

            $label = $kompetensi->pluck('nama')->join(', ') ?: '—';

            $adaBukti = $asesmen->evidenceItems->contains(fn ($e): bool => (int) $e->id_alat_penilaian === $idAlat);
            $semuaTerpenuhi = $kompetensiIds->isNotEmpty() && $kompetensiIds->every(
                fn (int $idK): bool => $pkByKompetensi->has($idK)
            );

            if (! $adaBukti && $kompetensiIds->isEmpty()) {
                $status = 'kosong';
                $statusKelas = 'text-on-surface-variant/70';
            } elseif ($semuaTerpenuhi) {
                $status = 'terisi';
                $statusKelas = 'text-emerald-600';
            } elseif ($adaBukti) {
                $status = 'belum_lengkap';
                $statusKelas = 'text-amber-600';
            } else {
                $status = 'kosong';
                $statusKelas = 'text-on-surface-variant/70';
            }

            $rows[] = [
                'kode' => (string) $tool->kode,
                'nama' => (string) $tool->nama,
                'kompetensi_label' => $label,
                'status' => $status,
                'status_kelas' => $statusKelas,
            ];
        }

        return $rows;
    }
}
