# Petunjuk Pengembangan & Penggunaan — AIS LPP 2026

Dokumen ini menjelaskan cara menyiapkan lingkungan, alur penggunaan aplikasi, serta rincian fitur yang tersedia pada **AI Assessment Support (AIS)** — sistem pendukung asesmen kompetensi berbasis web (Laravel 12).

---

## 1. Ringkasan Aplikasi

AIS mendukung siklus **asesmen kompetensi** untuk assessment center:

- **Master data** kamus kompetensi (kelompok, kompetensi, tingkat/indikator, alat penilaian, versi matriks, peserta).
- **Pemetaan kompetensi–alat** per versi matriks (“kamus”).
- **Asesmen** per peserta dengan preset alat sesuai tujuan (Promosi / Pemetaan talenta) dan opsi tanpa INTRAY (mis. BOD-3).
- **Pengumpulan bukti** manual per kompetensi atau otomatis lewat payload alat.
- **Perilaku kunci (KB)** yang dapat diisi manual atau diusulkan AI (OpenRouter).
- **Finalisasi** asesmen setelah cakupan kompetensi wajib terpenuhi.
- **Audit**: log aktivitas admin dan log pemanggilan AI.

Keputusan penilaian akhir tetap pada **asesor**; hasil AI wajib direview dan dapat diedit.

---

## 2. Persyaratan Teknis

| Komponen | Versi / catatan |
|----------|-----------------|
| PHP | ^8.2 |
| Composer | Terbaru |
| Node.js & npm | Untuk Vite (Tailwind CSS 4, Quill, SweetAlert2) |
| Database | SQLite (default dev) atau MySQL/MariaDB |
| Queue worker | Wajib untuk analisis AI async (`php artisan queue:listen`) |
| API AI | Akun OpenRouter + kunci API (opsional di dev tanpa AI) |

---

## 3. Instalasi & Menjalankan Aplikasi

### 3.1 Setup awal

```bash
cd c:\laragon\www\ais_lpp2026
composer install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database di `.env` (contoh SQLite: `DB_CONNECTION=sqlite` dan pastikan file `database/database.sqlite` ada).

```bash
php artisan migrate
php artisan db:seed
npm install
npm run build
```

### 3.2 Menjalankan server pengembangan

**Opsi A — satu perintah (disarankan):**

```bash
composer run dev
```

Menjalankan bersamaan: `php artisan serve`, `queue:listen`, `pail` (log), dan `npm run dev` (Vite).

**Opsi B — manual:**

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

Buka aplikasi di URL yang ditampilkan (biasanya `http://127.0.0.1:8000`).

### 3.3 Akun demo (setelah `db:seed`)

| Peran | Email | Kata sandi default |
|-------|-------|-------------------|
| Admin | `admin@example.com` | `password` |
| Konsultan | `konsultan@example.com` | `password` |

Peserta contoh: kode `DEMO-001`, matriks `KAMUS-17-DEFAULT`. Seeder juga membuat data asesmen contoh (manual + payload) lewat `ContohAssessmentSeeder`.

> **Keamanan:** Ganti kata sandi sebelum production. Jangan commit file `.env`.

### 3.4 Perintah berguna

| Perintah | Fungsi |
|----------|--------|
| `php artisan migrate` | Terapkan migrasi |
| `php artisan db:seed` | Isi master + contoh |
| `php artisan test` | Jalankan PHPUnit |
| `./vendor/bin/pint` | Format kode PHP |
| `npm run build` | Build aset produksi |

**Migrasi destruktif:** Perintah `migrate:fresh`, `migrate:refresh`, `db:wipe` diblokir kecuali `ALLOW_MIGRATE_FRESH=true` di `.env` (mencegah penghapusan data tidak sengaja).

---

## 4. Peran Pengguna (RBAC)

| Peran | Kode | Hak akses utama |
|-------|------|-----------------|
| **Admin** | `admin` | CRUD master data (termasuk **Pengguna**), **Sesi assessment**, penugasan admin penilai per asesmen, penugasan konsultan + token, impor CSV, log aktivitas/AI, batalkan finalisasi |
| **Konsultan** | `konsultan` | Lihat master data (read-only); setelah login + **token 8 karakter** — kelola hanya asesmen dalam penugasan token |

Middleware `role:admin` / `role:admin,konsultan` dan `konsultan.token` (gate token) mengatur route di `routes/web.php`.

### 4.1 Dua jenis “sesi”

| Nama tabel | Fungsi |
|------------|--------|
| `ais_sesi` | Sesi HTTP Laravel (cookie login) |
| `ais_sesi_asesmen` | **Sesi bisnis** assessment center — mengelompokkan banyak asesmen; setiap asesmen baru **wajib** punya `id_sesi_asesmen` |

### 4.2 Penugasan admin penilai

Baris di `ais_asesmen_asesor` dengan `jenis_penugasan = admin` (hanya user `peran=admin`). Admin boleh **melihat** semua asesmen, tetapi **mengubah** (bukti, PK, finalisasi, dll.) hanya jika ditugaskan di tabel tersebut. Pembuat asesmen otomatis ditambahkan sebagai penilai.

### 4.3 Token konsultan

Admin membuat **penugasan** di detail sesi (`ais_penugasan_konsultan`): konsultan, token 8 karakter `[A-Z0-9]`, daftar asesmen (pivot). Konsultan login email/kata sandi seperti biasa; saat membuka menu **Asesmen** tanpa token di session → form `/asesmen/token`. Setelah verifikasi, session menyimpan `penugasan_konsultan_id`; daftar asesmen terfilter. Tombol **Ganti token** menghapus session gate. Verifikasi dibatasi throttle (`konsultan-token`).

---

## 5. Navigasi & Alur Penggunaan

Setelah login, menu samping menampilkan:

1. **Dasbor** — ringkasan (widget Job Fit / GAP jika tersedia).
2. **Sesi assessment** *(admin)* — buat/edit sesi, detail sesi (daftar asesmen, buat asesmen, penugasan konsultan + token).
3. **Asesmen** — daftar & detail; admin buat asesmen dari dalam sesi; konsultan wajib token dulu.
4. **Master data** (submenu):
   - Ringkasan master
   - *(Admin)* **Pengguna**
   - Kelompok kompetensi … Peserta
   - *(Admin)* Log aktivitas, Log AI, Impor peserta CSV

### 5.1 Alur kerja admin

```mermaid
flowchart LR
    A[Login] --> B[Sesi assessment]
    B --> C[Buat asesmen dalam sesi]
    C --> D[Centang admin penilai]
    D --> E[Penugasan konsultan + token]
    E --> F[Kumpulkan bukti / PK]
    F --> G[Finalisasi]
```

### 5.2 Alur kerja konsultan

```mermaid
flowchart LR
    A[Login] --> B[Token 8 karakter]
    B --> C[Daftar asesmen terfilter]
    C --> D[Bukti / PK / AI]
```

---

## 6. Detail Fitur

### 6.1 Autentikasi

- Login email + kata sandi (`/login`); akun nonaktif (`aktif=false`) ditolak.
- Logout via tombol di sidebar.
- Tabel pengguna: `ais_pengguna` (`nama`, `alamat_surel`, `kata_sandi`, `peran`, `aktif`). CRUD admin: `/master/pengguna`.
- **Konsultan:** token akses penugasan (bukan kata sandi) — lihat §4.3.
- Laravel Sanctum: `ais_token_akses_pribadi` (API pribadi, terpisah dari token penugasan konsultan).

### 6.2 Master data

#### Kelompok kompetensi (`ais_kelompok_kompetensi`)

Tiga kelompok standar seed: **INT** (Inti), **MNJ** (Manajerial), **LDR** (Kepemimpinan).

- **Admin:** tambah, ubah, hapus (soft delete).
- **Konsultan:** hanya lihat.

#### Kompetensi (`ais_kompetensi`)

17 kompetensi kamus PTPN (kode INF, RSL, ACH, … MED). Setiap kompetensi terhubung ke kelompok, memiliki definisi, `tingkat_maksimum` (6), flag `aktif`.

#### Tingkat kompetensi (`ais_tingkat_kompetensi`)

Indikator perilaku per level 1–6 per kompetensi. Seed menyertakan indikator lengkap untuk 15 kompetensi; **BCR** dan **BSV** mendapat placeholder level 1–6 jika belum ada definisi resmi.

#### Alat penilaian (`ais_alat_penilaian`)

| Kode | Nama |
|------|------|
| BEI | Behavioral Event Interview |
| LGD | Leaderless Group Discussion |
| PA | Presentation Analysis |
| INT | Interview |
| INTRAY | In-Tray Exercise |
| RP | Role Play |
| CASE | Case Analysis |
| RA | Rencana Aksi |
| MI | Managerial Inventory |

#### Versi matriks (`ais_versi_matriks`)

Representasi **“kamus”** kompetensi per klien/konteks (mis. `KAMUS-17-DEFAULT` untuk 17 kompetensi PTPN). Field penting: `kode_versi`, `nama_versi`, `kunci_kamus`, `catatan_konteks`, `aktif`, `bawaan`, `dipublikasikan_pada`.

**Pemetaan kompetensi–alat** (`ais_pemetaan_kompetensi_alat`):

- Diakses dari **Versi matriks → Pemetaan**.
- Kisi centang kompetensi × alat dengan atribut `wajib`, `bobot`, `aktif`.
- Admin dapat **sinkron** grid sekaligus.

#### Peserta (`ais_peserta`)

Data peserta asesmen: kode, nama, email, jabatan, pendidikan, tanggal lahir, versi matriks default.

**Impor CSV (admin):**

- Menu: Master data → Impor peserta (CSV).
- Unduh template (pemisah koma atau titik koma, UTF-8 + BOM).
- Kolom wajib: `kode_peserta`, `nama_lengkap`.
- Kolom opsional: `alamat_surel`, `jabatan`, `pendidikan`, `tanggal_lahir`, `kode_versi_matriks`.

### 6.3 Asesmen

#### Membuat asesmen (`/asesmen/buat`)

Form meminta:

| Field | Keterangan |
|-------|------------|
| Peserta | Peserta aktif |
| Versi matriks | Kamus yang mengunci set kompetensi & pemetaan alat |
| Tujuan | `promosi` atau `pemetaan_talenta` |
| Metode koleksi bukti | `manual` atau `payload_alat` (dapat diubah di detail) |
| Tanpa INTRAY | Centang untuk skenario BOD-3 |
| Asesor | Opsional; multi-pilih admin/konsultan |

**Preset alat otomatis** (`AlatAsesmenPreset`):

| Tujuan | Alat (kode) |
|--------|-------------|
| Promosi | PA, INTRAY, LGD, RA, MI, BEI |
| Pemetaan talenta | PA, INTRAY, LGD, MI, BEI (tanpa RA) |

Jika **Tanpa INTRAY** dicentang, kode `INTRAY` dihilangkan dari preset.

Status awal: **draf**. Alat yang dipilih mengacu pada master aktif dan flag `wajib` dari pemetaan matriks.

#### Status asesmen

| Status | Arti |
|--------|------|
| `draf` | Masih dapat diedit |
| `berlangsung` | *(reserved)* |
| `terintegrasi` | *(reserved — integrasi skor)* |
| `selesai_final` | Difinalisasi; input terkunci kecuali admin batalkan finalisasi |

#### Metode koleksi bukti

**A. Manual (`manual`)**

- Tambah **bukti penilaian** per kombinasi alat + kompetensi.
- Editor mendukung teks kaya (Quill); disimpan juga versi `teks_mentah`, `teks_mentah_rich`, `teks_mentah_normalized`.
- Tombol **Analisis AI** per baris bukti → usulan tingkat, kutipan verbatim, alasan konfirmatori → membuat/memperbarui **perilaku kunci** terkait.

**B. Otomatis — payload alat (`payload_alat`)**

- Unggah **teks muatan** per alat (satu payload per alat, dapat banyak entri).
- Tombol **Analisis AI bulk** pada payload → AI memetakan ke banyak kompetensi sekaligus dan membuat entri perilaku kunci (wajib direview asesor).

Metode dapat diubah di halaman detail asesmen selama belum final.

#### Perilaku kunci (`ais_perilaku_kunci`)

- Dapat ditambah manual atau dari hasil AI.
- Field: alat, kompetensi, bukti terkait (opsional), tingkat kompetensi, teks perilaku, alasan pemilihan, **kutipan referensi** (jangkar ke teks mentah).
- Edit form terpisah untuk koreksi pasca-AI.

#### Finalisasi

- Tombol finalisasi memeriksa **cakupan kompetensi wajib**: setiap kompetensi wajib harus punya minimal satu perilaku kunci **disahkan** (`tervalidasi = true`) dengan `id_tingkat_kompetensi` terisi (aturan sama dengan input pratinjau integrasi).
- Hanya **admin** yang dapat **membatalkan finalisasi** (kembali ke draf).

### 6.4 Fitur AI (OpenRouter)

**Aktivasi:**

- Isi `OPENROUTER_API_KEY` (atau `AI_OPENROUTER_API_KEY`) di `.env`.
- Opsional: `AI_AKTIF=false` untuk memaksa mati meski kunci ada.
- Tanpa `AI_AKTIF` eksplisit: aktif otomatis jika kunci terisi.

**Konfigurasi utama** (`config/ai.php`):

| Variabel | Default / fungsi |
|----------|------------------|
| `OPENROUTER_MODEL` | `google/gemini-2.0-flash-001` |
| `OPENROUTER_TIMEOUT` | 90 detik |
| `OPENROUTER_TEMPERATURE` | 0.2 |
| `AI_QUEUE_CONNECTION` | `database` |
| `AI_QUEUE_NAME` | `ai-analysis` |
| `AI_TRIGGER_PER_MINUTE` | 20 (throttle trigger UI) |

**Master data AI (opsi C):**

| Master | Menu | Fungsi |
|--------|------|--------|
| **Template prompt AI** | Master data → Template prompt AI | Kerangka analisis (mis. STAR) — teks instruksi digabung ke prompt sistem untuk **semua alat** |
| **Model AI** | Master data → Model AI (OpenRouter) | Daftar model yang boleh dipilih; jika tabel berisi baris aktif, menggantikan `AI_OPENROUTER_MODELS` di `.env` |

- **Default master:** di form template, centang **alat penilaian** yang memakai template tersebut (mis. STAR → BEI).
- **Per asesmen per alat:** di detail asesmen, tabel «Template prompt AI» — mode *Ikuti master* / *Tanpa template* / *Template khusus* per alat; tombol «Terapkan ke semua alat» untuk set massal.
- Urutan resolusi efektif: override per alat asesmen → pemetaan master template–alat → fallback `id_template_prompt_ai` global asesmen.
- Seeder: `AiMasterSeeder` (STAR terhubung BEI; model dari `.env` bila kosong).
- Log AI: metadata `prompt_template` berisi kode, nama, dan `sumber` (`master` | `asesmen_per_alat` | `asesmen_global`).

**Prinsip governance AI:**

- Prompt memaksa **kutipan verbatim** dari teks mentah; tidak mengarang fakta di luar bukti.
- Field konfirmatori menjelaskan kaitan kutipan dengan indikator level.
- Semua pemanggilan dicatat di `ais_log_ai` (admin dapat audit di **Log AI**).

**Job antrian:** `AnalyzeEvidenceAiJob`, `AnalyzeToolPayloadAiJob` — pastikan worker queue berjalan.

### 6.5 Log & audit

| Fitur | Akses | Isi |
|-------|-------|-----|
| **Log aktivitas** | Admin | Aksi penting (asesmen dibuat, AI dijalankan, finalisasi, perubahan master, dll.) |
| **Log AI** | Admin | Request/response ringkas, model, status, referensi entitas |

### 6.6 Soft delete

Master data utama mendukung **soft delete** (`dihapus_pada`). Data terhapus tidak muncul di daftar default tetapi tetap di database.

---

## 7. Konfigurasi Lingkungan (`.env`)

Selain database dan `APP_URL`, perhatikan:

```env
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# AI
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_MODEL=google/gemini-2.0-flash-001
AI_QUEUE_CONNECTION=database
AI_QUEUE_NAME=ai-analysis

# Keamanan migrasi
ALLOW_MIGRATE_FRESH=false
```

Untuk production: `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, backup database, dan rotasi kunci API.

---

## 8. Struktur Data & Konvensi

- Prefix tabel domain: **`ais_`**.
- Nama tabel domain berbahasa Indonesia (`ais_kompetensi`, `ais_asesmen`, dll.).
- Kode kompetensi kelompok **INT** ≠ kode alat **INT** (domain berbeda).
- Versi matriks = “kamus” yang menentukan subset kompetensi dan pemetaan alat per klien.

### Seeder

| Seeder | Fungsi |
|--------|--------|
| `MasterDataSeeder` | Kelompok, 17 kompetensi, indikator, 9 alat, matriks default, pemetaan BEI/PA, peserta demo |
| `ContohVersiMatriksPaketSeeder` | Paket matriks tambahan (jika ada di repo) |
| `ContohAssessmentSeeder` | Asesmen contoh untuk UI |
| `DatabaseSeeder` | Mengorkestrasi semua + user admin & konsultan |

---

## 9. Pengujian

```bash
php artisan test
```

Suite mencakup antara lain: RBAC master data, mode koleksi bukti, analisis AI, antrian AI, preset alat asesmen.

---

## 10. Fitur dalam Rencana (belum / sebagian tersedia)

Berdasarkan rencana pengembangan MVP, berikut **belum** atau **baru sebagian** di UI:

| Area | Status |
|------|--------|
| Dasbor GAP & Job Fit | Ada (ringkasan pratinjau) |
| Mesin integrasi skor berbobot | Ada (`CompetencyIntegrationService`, pratinjau) |
| Laporan naratif + export PDF | Belum |
| Wizard assessment lengkap (job target, snapshot matriks dinamis) | Sebagian (form buat asesmen sudah ada) |
| API eksternal dokumentasi publik | Sanctum ada; endpoint bisnis terbatas |

Gunakan dokumen rencana internal (`.cursor/plans/`) untuk detail fase berikutnya.

---

## 10b. Bukti wawancara (STT)

Tab **Pengumpulan Bukti** (mode manual) mendukung **Bukti teks** atau **Bukti wawancara** (upload audio).

### Penyedia STT

| Penyedia | Biaya | Bahasa Indonesia | File besar |
|----------|-------|------------------|------------|
| **Groq** (`STT_PROVIDER=groq`) | **Gratis** (tier Groq, batas rate) | Ya (Whisper) | Chunk otomatis via FFmpeg jika &gt; 24 MB |
| **OpenRouter** (`STT_PROVIDER=openrouter`) | **Berbayar** (~$0.006/menit Whisper) | Ya | Unggah max `STT_MAX_FILE_MB`; chunk FFmpeg sama |

**Whisper di OpenRouter tidak gratis.** Tidak ada model STT `:free` di OpenRouter.

Daftar kunci Groq gratis: [console.groq.com](https://console.groq.com) → API Keys.

| Variabel `.env` | Fungsi |
|-----------------|--------|
| `STT_PROVIDER` | `groq` (default, gratis) atau `openrouter` (berbayar) |
| `GROQ_API_KEY` | Wajib untuk Groq |
| `GROQ_STT_MODEL` | Default `whisper-large-v3-turbo` |
| `STT_AKTIF` | `false` mematikan transkripsi otomatis |
| `STT_MODEL` | Model OpenRouter bila `STT_PROVIDER=openrouter` |
| `STT_LANGUAGE` | Kode ISO, mis. `id` |
| `STT_MAX_FILE_MB` | Batas unggah ke aplikasi (default 100 MB) |
| `STT_CHUNK_MAX_MB` | Ukuran maks per request API (default 24 MB) |
| `STT_FFMPEG_PATH` | Path FFmpeg jika tidak ada di PATH (Laragon: `C:\laragon\bin\ffmpeg\...\ffmpeg.exe`) |
| `STT_QUEUE_CONNECTION` | `sync` = langsung; `database` = butuh worker |

File audio &gt; 24 MB otomatis dipecah (10 menit/chunk, overlap 10 detik) selama FFmpeg terpasang.

Jika kredit/kuota habis atau STT gagal, asesor dapat mengoreksi teks manual di **Perbarui bukti**.

---

## 11. Pemecahan Masalah

| Gejala | Solusi |
|--------|--------|
| Transkripsi wawancara gagal (502) | Gunakan model Whisper (`STT_MODEL=openai/whisper-large-v3-turbo`); Parakeet tidak mendukung Indonesia; perkecil audio &lt; 25 MB |
| Transkripsi wawancara gagal | Cek kredit OpenRouter; isi transkrip manual; pastikan `STT_AKTIF=true` |
| Tombol AI tidak muncul | Pastikan kunci OpenRouter terisi dan `AI_AKTIF` tidak `false` |
| Analisis AI menggantung | Jalankan `php artisan queue:listen`; cek `failed_jobs` |
| `429` saat trigger AI | Tunggu (throttle `AI_TRIGGER_PER_MINUTE`) |
| Finalisasi ditolak | Lengkapi perilaku kunci + tingkat untuk semua kompetensi wajib di ringkasan |
| `migrate:fresh` ditolak | Set sementara `ALLOW_MIGRATE_FRESH=true` hanya di dev |
| Aset CSS/JS kosong | `npm run dev` atau `npm run build` |

---

## 12. Referensi Kode

| Area | Lokasi |
|------|--------|
| Route web | `routes/web.php` |
| Controller asesmen | `app/Http/Controllers/AssessmentController.php` |
| Preset alat | `app/Services/Assessment/AlatAsesmenPreset.php` |
| AI bukti / bulk | `app/Services/Ai/EvidenceAiAnalyzer.php`, `BulkToolPayloadAiAnalyzer.php` |
| Konfigurasi AI | `config/ai.php` |
| Seed master | `database/seeders/MasterDataSeeder.php` |
| Data seed array | `database/seeders/data/master_seed_arrays.php` |

---

*Terakhir diselaraskan dengan codebase AIS LPP 2026 — Laravel 12, PHP 8.2+.*
