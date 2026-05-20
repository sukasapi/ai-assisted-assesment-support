<?php

namespace App\Http\Controllers;

use App\Enums\AssessmentEvidenceCollectionMode;
use App\Enums\AssessmentPurpose;
use App\Enums\AssessmentStatus;
use App\Http\Requests\StoreAssessmentRequest;
use App\Http\Requests\StoreAssessmentToolPayloadRequest;
use App\Http\Requests\StoreEvidenceRequest;
use App\Http\Requests\StoreKeyBehaviorRequest;
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
use App\Support\PayloadAnalysisPresenter;
use App\Support\ToolPayloadDeletionGuard;
use App\Services\Ai\EvidenceAiAnalyzer;
use App\Services\Assessment\AlatAsesmenPreset;
use App\Services\Assessment\AssessmentToolAvailabilityDiagnostic;
use App\Support\AiFeature;
use App\Support\CatatAktivitas;
use App\Support\BulkTextNormalizer;
use App\Support\EvidenceTextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        ]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $tujuan = AssessmentPurpose::from($request->string('tujuan')->toString());
        $metode = AssessmentEvidenceCollectionMode::from($request->string('metode_koleksi_bukti')->toString());

        $asesmen = DB::transaction(function () use ($request, $tujuan, $metode): Assessment {
            /** @var Assessment $row */
            $row = Assessment::query()->create([
                'id_peserta' => $request->integer('id_peserta'),
                'id_versi_matriks' => $request->integer('id_versi_matriks'),
                'tujuan' => $tujuan,
                'status' => AssessmentStatus::Draf,
                'tanpa_intray' => $request->boolean('tanpa_intray'),
                'metode_koleksi_bukti' => $metode,
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
        $ringkasanFinalisasi = $this->ringkasanCakupanKompetensiWajib($asesmen);
        $gridCakupanKompetensi = $this->gridCakupanKompetensi($asesmen, $ringkasanFinalisasi);
        $ringkasanAlatPreset = $this->ringkasanAlatPreset($asesmen, $pemilihanAlatPreset, $pemetaanKompetensiAlat);
        $buktiPerAlat = $asesmen->evidenceItems->groupBy('id_alat_penilaian');
        $persenProgress = $ringkasanFinalisasi['total_wajib'] > 0
            ? (int) round(($ringkasanFinalisasi['total_terpenuhi'] / $ringkasanFinalisasi['total_wajib']) * 100)
            : 0;

        return view('assessments.show', [
            'asesmen' => $asesmen,
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
        ]);
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

        $asesmen->update(['metode_koleksi_bukti' => $baru]);

        CatatAktivitas::catat(
            $request->user(),
            'asesmen.metode_koleksi_bukti.diubah',
            Assessment::class,
            $asesmen->id,
            [
                'dari' => $lama->value,
                'ke' => $baru->value,
            ],
        );

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Metode koleksi bukti diperbarui.');
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
        ]);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Perilaku kunci ditambahkan.');
    }

    public function editKeyBehavior(Assessment $asesmen, KeyBehavior $perilaku): View
    {
        $this->authorize('update', $asesmen);
        abort_unless($perilaku->id_asesmen === $asesmen->id, 404);

        $perilaku->load(['tool', 'competency', 'competencyLevel', 'evidence']);

        $tingkatUntukKompetensi = CompetencyLevel::query()
            ->where('id_kompetensi', $perilaku->id_kompetensi)
            ->whereNull('dihapus_pada')
            ->orderBy('tingkat')
            ->get();

        return view('assessments.key-behaviors.edit', [
            'asesmen' => $asesmen,
            'perilaku' => $perilaku,
            'tingkatUntukKompetensi' => $tingkatUntukKompetensi,
        ]);
    }

    public function updateKeyBehavior(UpdateKeyBehaviorRequest $request, Assessment $asesmen, KeyBehavior $perilaku): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless($perilaku->id_asesmen === $asesmen->id, 404);

        $tingkatBaru = $request->filled('id_tingkat_kompetensi')
            ? $request->integer('id_tingkat_kompetensi')
            : null;
        $teksDariForm = $request->string('teks_perilaku')->toString();
        $tingkatLama = $perilaku->id_tingkat_kompetensi;

        $teksSimpan = $teksDariForm;
        if ($tingkatBaru !== null && (int) $tingkatBaru !== (int) ($tingkatLama ?? 0)) {
            $teksSimpan = CompetencyLevel::teksIndikatorResmi($tingkatBaru) ?? $teksDariForm;
        }

        $perilaku->update([
            'id_tingkat_kompetensi' => $tingkatBaru,
            'teks_perilaku' => $teksSimpan,
            'alasan_pemilihan' => $request->filled('alasan_pemilihan')
                ? $request->string('alasan_pemilihan')->toString()
                : null,
            'kutipan_referensi' => $request->filled('kutipan_referensi')
                ? $request->string('kutipan_referensi')->toString()
                : null,
        ]);

        return redirect()
            ->route('asesmen.show', $asesmen)
            ->with('status', 'Perilaku kunci diperbarui.');
    }

    public function analyzeEvidenceAi(Assessment $asesmen, Evidence $bukti): RedirectResponse
    {
        $this->authorize('update', $asesmen);
        abort_unless($bukti->id_asesmen === $asesmen->id, 404);

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

        $ringkasan = $this->ringkasanCakupanKompetensiWajib($asesmen);
        if ($ringkasan['total_kompetensi_kurang'] > 0) {
            return redirect()
                ->route('asesmen.show', $asesmen)
                ->withErrors([
                    'finalisasi' => 'Finalisasi belum bisa dilakukan. Masih ada kompetensi wajib tanpa tingkat indikator perilaku dari evidence/perilaku kunci.',
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
    private function ringkasanCakupanKompetensiWajib(Assessment $asesmen): array
    {
        $alatAktif = AssessmentToolSelection::query()
            ->where('id_asesmen', $asesmen->id)
            ->where('aktif', true)
            ->pluck('id_alat_penilaian')
            ->all();

        if ($alatAktif === []) {
            return [
                'total_wajib' => 0,
                'total_terpenuhi' => 0,
                'total_kompetensi_kurang' => 0,
                'kompetensi_kurang' => [],
            ];
        }

        $mapWajib = CompetencyToolMapping::query()
            ->where('id_versi_matriks', $asesmen->id_versi_matriks)
            ->whereIn('id_alat_penilaian', $alatAktif)
            ->where(function ($query): void {
                $query->where('aktif', true)->orWhereNull('aktif');
            })
            ->where('wajib', true)
            ->with('competency')
            ->get();

        $idKompetensiWajib = $mapWajib->pluck('id_kompetensi')->unique()->values();
        if ($idKompetensiWajib->isEmpty()) {
            return [
                'total_wajib' => 0,
                'total_terpenuhi' => 0,
                'total_kompetensi_kurang' => 0,
                'kompetensi_kurang' => [],
            ];
        }

        $idKompetensiTerpenuhi = KeyBehavior::query()
            ->where('id_asesmen', $asesmen->id)
            ->whereIn('id_kompetensi', $idKompetensiWajib->all())
            ->whereNotNull('id_tingkat_kompetensi')
            ->pluck('id_kompetensi')
            ->unique()
            ->values();

        $idKompetensiKurang = $idKompetensiWajib
            ->diff($idKompetensiTerpenuhi)
            ->values();

        $kompetensiKurang = $mapWajib
            ->filter(fn (CompetencyToolMapping $m): bool => $idKompetensiKurang->contains($m->id_kompetensi))
            ->map(fn (CompetencyToolMapping $m): array => [
                'kode' => (string) ($m->competency?->kode_kompetensi ?? '-'),
                'nama' => (string) ($m->competency?->nama ?? 'Kompetensi'),
            ])
            ->unique('kode')
            ->sortBy('kode')
            ->values()
            ->all();

        return [
            'total_wajib' => $idKompetensiWajib->count(),
            'total_terpenuhi' => $idKompetensiTerpenuhi->count(),
            'total_kompetensi_kurang' => $idKompetensiKurang->count(),
            'kompetensi_kurang' => $kompetensiKurang,
        ];
    }

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

        $idTerpenuhi = KeyBehavior::query()
            ->where('id_asesmen', $asesmen->id)
            ->whereNotNull('id_tingkat_kompetensi')
            ->pluck('id_kompetensi')
            ->flip();

        $idAdaPk = KeyBehavior::query()
            ->where('id_asesmen', $asesmen->id)
            ->pluck('id_kompetensi')
            ->flip();

        $grid = [];
        foreach ($kompetensiWajib->take(8) as $c) {
            $terpenuhi = $idTerpenuhi->has($c->id);
            $adaPk = $idAdaPk->has($c->id);
            $persen = $terpenuhi ? 100 : ($adaPk ? 40 : 0);
            $grid[] = [
                'nama' => (string) $c->nama,
                'kode' => (string) $c->kode_kompetensi,
                'persen' => $persen,
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
