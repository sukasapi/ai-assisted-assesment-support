# Development history

## [2026-05-23]-[Master pengguna, sesi assessment, penugasan admin & token konsultan]

- **Migrasi:** `ais_sesi_asesmen`, FK wajib `ais_asesmen.id_sesi_asesmen` (sesi legacy `SES-LEGACY`), `aktif` di `ais_pengguna`, `jenis_penugasan` di `ais_asesmen_asesor`, `ais_penugasan_konsultan` + pivot asesmen.
- **Master admin:** CRUD pengguna (`master/pengguna`); konsultan tidak boleh akses.
- **Sesi:** CRUD `sesi-asesmen`; buat asesmen hanya dari dalam sesi (`sesi-asesmen.asesmen.store`); redirect `asesmen/buat` → daftar sesi.
- **Otorisasi:** admin **update** asesmen hanya jika ditugaskan di `ais_asesmen_asesor` (`jenis_penugasan=admin`); konsultan login biasa lalu **token 8 karakter** (`EnsureKonsultanPenugasanToken`, throttle `konsultan-token`).
- **Tes:** `SessionUserAndTokenTest`, helper `AssessmentTestHelpers`; tes lama memakai route sesi.
- **Dokumen:** bagian sesi & token di `petunjuk_dev.MD`.

## [2026-05-22]-[Template prompt per alat: master default + override asesmen]

- **Migrasi:** `ais_pemetaan_template_alat`, `ais_asesmen_template_alat` (mode: master | none | custom).
- **Resolver:** `AiPromptTemplateResolver` — urutan: override per alat → master template–alat → global asesmen.
- **UI:** centang alat di master template; tabel per alat di detail asesmen + terapkan massal.
- **Seeder:** STAR ↔ BEI. **Tes:** `AiPromptTemplateResolverTest`, perluasan feature test.

## [2026-05-22]-[Master AI: template prompt (opsi C) + model OpenRouter di database]

- **Migrasi:** `ais_template_prompt_ai`, `ais_model_ai`, FK `ais_asesmen.id_template_prompt_ai`.
- **Master admin:** CRUD template prompt & model AI; seeder `AiMasterSeeder` (STAR + impor model dari `.env`).
- **Asesmen:** pilih template saat buat / ubah di detail; `AiPromptComposer` menggabung instruksi ke prompt inkremental & bulk (semua alat).
- **Katalog model:** `AiModelCatalog` prioritas data DB aktif, fallback `.env`.
- **Tes:** `AiPromptComposerTest`, `AiMasterAndTemplateTest`, perluasan `AiModelCatalogTest`.

## [2026-05-20]-[Phase 4 (inti): integrasi pratinjau GAP/Job Fit, aturan PK disahkan, finalisasi selaras]

- **Aturan PK & finalisasi:** `MandatoryCompetencyCoverage` — kompetensi wajib terpenuhi hanya jika ada PK `tervalidasi=true` + tingkat + pemetaan/alat aktif. PK manual otomatis disahkan saat tambah.
- **Migrasi:** `ais_integrasi_kompetensi`; kolom `ais_asesmen.job_fit_persen_pratinjau`, `integrasi_pratinjau_pada`.
- **Layanan:** `CompetencyIntegrationService` (formula `v1`: rata-rata tertimbang level×bobot, target promosi +1 / talenta default 4).
- **HTTP:** `POST /asesmen/{asesmen}/integrasi/hitung`; log `asesmen.integrasi_dihitung`.
- **UI:** panel pratinjau di detail asesmen; widget dasbor Job Fit & GAP terbesar.
- **Tes:** `CompetencyIntegrationTest`, `AssessmentFinalizationTest` (termasuk tolak PK draft).
- **Dokumen:** `documentation/phase_4_ceklist.md` §0.1 aturan resmi + rumus.

## [2026-05-11]-[Phase 2 (inti): asesmen, preset alat, bukti, perilaku kunci, impor CSV peserta]

- **Migrasi:** `ais_asesmen`, `ais_asesmen_asesor`, `ais_pemilihan_alat_asesmen`, `ais_bukti_penilaian`, `ais_perilaku_kunci` ([database/migrations/2026_05_13_120000_create_ais_assessment_core_tables.php](c:\laragon\www\aiassisstedconsultan\database\migrations\2026_05_13_120000_create_ais_assessment_core_tables.php)).
- **Enum:** `AssessmentStatus`, `AssessmentPurpose`. **Model:** `Assessment`, `AssessmentAssessor`, `AssessmentToolSelection`, `Evidence`, `KeyBehavior`. **Kebijakan:** `AssessmentPolicy` + registrasi di `AppServiceProvider`.
- **Layanan:** `AlatAsesmenPreset` — alat PA/INTRAY/LGD/BEI sesuai tujuan; opsi `tanpa_intray` menghapus INTRAY dari preset; `wajib` mengikuti `ais_pemetaan_kompetensi_alat` per versi matriks.
- **HTTP:** `AssessmentController` (indeks, buat, simpan, detail, tambah bukti, tambah perilaku kunci), `ParticipantImportController` (form + unggah CSV). **Validasi:** `StoreAssessmentRequest`, `StoreEvidenceRequest`, `StoreKeyBehaviorRequest`, `ImportParticipantsCsvRequest`.
- **Rute (middleware `auth` + `role:admin,konsultan`):** `/asesmen`, `/asesmen/buat`, `POST /asesmen`, `/asesmen/{asesmen}`, unggah bukti & perilaku kunci; `GET|POST /peserta/impor-csv`.
- **UI:** Blade `assessments/*`, `participants/import`, navigasi di [resources/views/layouts/app.blade.php](c:\laragon\www\aiassisstedconsultan\resources\views\layouts\app.blade.php).
- **Seeder:** peserta demo `DEMO-001` di `MasterDataSeeder` (terikat `KAMUS-17-DEFAULT`).
- **Tes:** [tests/Feature/AssessmentAccessTest.php](c:\laragon\www\aiassisstedconsultan\tests\Feature\AssessmentAccessTest.php) (guest redirect, admin boleh indeks asesmen).

**Konfirmasi Phase 3:** integrasi AI pada bukti / perilaku kunci setelah Anda setujui.

## [2026-05-12]-[Kolom tabel domain Bahasa Indonesia + timestamp dibuat/diperbarui]

- **Kolom `ais_pengguna`:** `nama`, `alamat_surel`, `diverifikasi_pada`, `kata_sandi`, `peran`, `token_ingat`, `dibuat_pada`, `diperbarui_pada`. Model `User`: `authPasswordName`, `rememberTokenName`, aksesor kompatibilitas (`name`, `email`, `role`, `password`, `remember_token`, `email_verified_at`). Login: `Auth::attempt` memakai `alamat_surel` (input form tetap `email`).
- **Domain:** FK `id_kelompok_kompetensi`, `id_kompetensi`, `id_alat_penilaian`, `id_versi_matriks`, `id_pengguna`; pengganti nama kolom sesuai migrasi `2026_05_11_100000_*` (mis. `kode_kompetensi`, `indikator_perilaku`, `bobot`, `wajib`, `subjek_tipe`/`subjek_id`, `properti`, `alamat_ip`).
- **Timestamp:** semua tabel domain (kecuali `ais_log_aktivitas` hanya `dibuat_pada`) memakai `dibuat_pada` / `diperbarui_pada`; konstanta `CREATED_AT` / `UPDATED_AT` di model.
- **Pengecualian:** `ais_sesi`, `ais_token_reset_kata_sandi`, `ais_token_akses_pribadi` — kolom tetap sesuai kontrak Laravel/Sanctum (lihat komentar migrasi).
- **Seeder:** [database/seeders/data/master_seed_arrays.php](c:\laragon\www\aiassisstedconsultan\database\seeders\data\master_seed_arrays.php) memakai kunci array selaras kolom (`kode`, `nama`, `kode_kelompok`, `definisi`, `deskripsi`, `urutan`).
- **Plan:** [.cursor/plans/ai_assessment_mvp_plan_d91a03dd.plan.md](c:\laragon\www\aiassisstedconsultan\.cursor\plans\ai_assessment_mvp_plan_d91a03dd.plan.md) — bagian **Konvensi penamaan kolom**.

## [2026-05-12]-[Penamaan tabel domain Bahasa Indonesia, prefix ais_ tetap]

- **Pengguna & framework:** `ais_pengguna` (dulu `ais_users`), `ais_token_reset_kata_sandi`, `ais_sesi`, Sanctum → `ais_token_akses_pribadi` + model [app/Models/PersonalAccessToken.php](c:\laragon\www\aiassisstedconsultan\app\Models\PersonalAccessToken.php) + `Sanctum::usePersonalAccessTokenModel` di [app/Providers/AppServiceProvider.php](c:\laragon\www\aiassisstedconsultan\app\Providers\AppServiceProvider.php).
- **Domain:** `ais_tingkat_kompetensi` (dulu `ais_level_kompetensi`), `ais_alat_penilaian` (`ais_tools_assessment`), `ais_versi_matriks` (`ais_matrix_versions`), `ais_pemetaan_kompetensi_alat` (`ais_mapping_kompetensi_tools`), `ais_log_aktivitas` (`ais_activity_logs`). Tetap: `ais_kelompok_kompetensi`, `ais_kompetensi`, `ais_peserta`.
- **Konfigurasi:** [config/auth.php](c:\laragon\www\aiassisstedconsultan\config\auth.php) (tabel reset), [config/session.php](c:\laragon\www\aiassisstedconsultan\config\session.php) (`ais_sesi`).
- **Dokumen rencana:** [ai_assessment_mvp_plan_d91a03dd.plan.md](C:\Users\L e n o v o\.cursor\plans\ai_assessment_mvp_plan_d91a03dd.plan.md) diperbarui (konvensi penamaan + referensi tabel).

## [2026-05-11]-[Phase 1: fondasi — Sanctum, ais_pengguna+role, master data, auth, seeder]

- **Paket:** `laravel/sanctum` + migrasi token akses pribadi (`ais_token_akses_pribadi`).
- **Auth DB:** migrasi `0001_...` membuat `ais_pengguna` (kolom `role`: `admin` | `konsultan`), `ais_token_reset_kata_sandi`, `ais_sesi`.
- **Domain:** migrasi `2026_05_11_100000_create_ais_foundation_tables` — `ais_kelompok_kompetensi`, `ais_kompetensi`, `ais_tingkat_kompetensi`, `ais_alat_penilaian`, `ais_versi_matriks`, `ais_pemetaan_kompetensi_alat`, `ais_peserta`, `ais_log_aktivitas`.
- **Model:** `User` (`ais_pengguna`, `HasApiTokens`), `CompetencyGroup`, `Competency`, `CompetencyLevel`, `AssessmentTool`, `MatrixVersion`, `CompetencyToolMapping`, `Participant`, `ActivityLog`, `PersonalAccessToken`.
- **HTTP:** `LoginController`, `DashboardController`, rute `login` / `logout` / `dashboard`, layout Blade + Vite; alias middleware `role` (`EnsureUserHasRole`) untuk fase berikutnya.
- **Seeder:** `database/seeders/data/master_seed_arrays.php` + `MasterDataSeeder` (3 kelompok, 17 kompetensi, indikator 6 level — BCR/BSV placeholder teks, 7 alat, matrix `KAMUS-17-DEFAULT` + mapping BEI wajib & PA opsional per kompetensi). `DatabaseSeeder` membuat 2 pengguna uji.
- **Tes:** `tests/Feature/ExampleTest.php` disesuaikan (root mengarahkan ke login).
- **Akun uji (kata sandi factory: `password`):** `admin@example.com` (admin), `konsultan@example.com` (konsultan).

**Phase 2:** telah dimulai — lihat entri [2026-05-11] di atas.
