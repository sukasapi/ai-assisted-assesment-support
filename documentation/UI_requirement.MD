# Persyaratan UI — AIS LPP 2026

Dokumen ini mendefinisikan **panduan antarmuka** untuk **AI Assessment Support (AIS)**. Implementasi: Blade + Tailwind CSS 4 + Vite + komponen `<x-ui.*>`.

**Design system aktif:** [Kinetic Professional](./UIdesign/kinetic_professional/DESIGN.md) — primary `#0058be`; AI memakai `secondary-container` (bukan violet).

**Dokumen terkait:** [petunjuk_dev.MD](./petunjuk_dev.MD)

---

## 1. Prinsip Desain

| Prinsip | Penjelasan |
|---------|------------|
| **Profesional & tenang** | Surface `#f9f9ff`, kartu putih dengan `card-depth`; kontras cukup untuk teks panjang asesmen. |
| **Orientasi tugas** | Alur utama: master → buat asesmen → kumpulkan bukti → review perilaku kunci → finalisasi. |
| **Kejelasan peran AI** | Elemen AI memakai **biru lembut** (`secondary-container`, tombol `variant="ai"`); status bisnis memakai emerald/amber/error. |
| **Data padat, terbaca** | Tabel lebar dengan scroll horizontal; kolom pertama sticky pada kisi matriks. |
| **Bahasa Indonesia** | Label UI, pesan sukses/galat, dan placeholder dalam Bahasa Indonesia. |
| **Governance evidence** | Teks mentah, kutipan, dan konfirmatori harus terlihat jelas; AI tidak disamarkan sebagai keputusan final. |

---

## 2. Sistem Desain (Design Tokens)

### 2.1 Tipografi

| Token | Nilai |
|-------|--------|
| Font body | **Hanken Grotesk** (`resources/css/app.css`) |
| Font headline | **Plus Jakarta Sans** (`font-display`) |
| Judul halaman | `font-display text-2xl/3xl font-bold text-on-surface` |
| Subjudul / section | `text-sm font-bold uppercase tracking-wide text-on-surface-variant` |
| Body | `text-sm text-on-surface` / `text-on-surface-variant` |
| Kode (kompetensi, alat) | `font-mono` + ukuran `text-xs` bila di tabel |

### 2.2 Palet warna (Tailwind)

| Penggunaan | Kelas utama |
|------------|-------------|
| Latar aplikasi | `bg-surface` |
| Kartu / panel | `card-depth` / `bg-surface-container-lowest` |
| Teks utama | `text-on-surface` |
| Teks sekunder | `text-on-surface-variant` |
| Aksi primer | `accent-gradient` / `bg-primary` |
| Aksi sekunder | `border-outline-variant bg-surface-container-lowest` |
| Sukses / finalisasi badge | `text-emerald-700`, badge `tone="success"` |
| Peringatan | `alert-banner type="warning"` / amber |
| Bahaya | `text-error`, `variant="danger"` |
| Fitur AI | `ai-accent`, `<x-ui.button variant="ai">` |
| Primary brand | `#0058be` (`text-primary`, `bg-primary`) |

### 2.3 Spasi & layout

| Elemen | Spesifikasi |
|--------|-------------|
| Lebar konten utama | `max-w-6xl` (terpusat di area main) |
| Form sempit | `max-w-md` (login, impor) / `max-w-xl` (buat asesmen) / `max-w-lg` (CRUD master) |
| Padding main | `px-4 py-8 lg:px-8` |
| Sidebar | `w-sidebar-width` (~72px), ikon Material Symbols, indikator aktif biru |
| Top bar | Sticky header dengan `@yield('topbar_back')`, nama pengguna |
| Gap antar section | `mb-8` pada blok section halaman detail |

### 2.4 Komponen standar

#### Tombol

```
Primer:    rounded-md bg-zinc-900 px-3|4 py-1.5|2 text-sm font-medium text-white hover:bg-zinc-800
Sekunder:  rounded-md border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-800 hover:bg-zinc-50
AI:        rounded border border-violet-300 bg-violet-50 px-2 py-1 text-xs font-medium text-violet-900
Finalisasi: bg-emerald-900 (sama pola primer, warna emerald)
Disabled:  disabled:cursor-not-allowed disabled:bg-zinc-400
```

#### Input teks / select

```
mt-1 w-full rounded-md border border-zinc-300 px-2|3 py-1.5|2 text-sm
focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500
disabled:bg-zinc-100 disabled:text-zinc-500
```

#### Tabel data

```
Wrapper: overflow-hidden|overflow-x-auto rounded-lg border border-zinc-200 bg-white shadow-sm
Header:  bg-zinc-50 text-xs font-medium uppercase text-zinc-500
Baris:   divide-y divide-zinc-100, hover:bg-zinc-50/80
```

#### Kartu / section

```
rounded-lg border border-zinc-200 bg-white p-5 shadow-sm
```

#### Pesan inline (tanpa modal)

- **Info / peringatan:** `rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900`
- **Sukses ringkas:** `text-emerald-700` / `border-emerald-100 bg-emerald-50`
- **Error field:** `text-xs text-rose-700` di bawah input

#### Modal notifikasi (SweetAlert2)

- Tema: `confirmButtonColor: #27272a`, `cancelButtonColor: #71717a`
- Dipicu dari JSON `#app-alerts` (flash `status`, `error`, validasi)
- Loading AI: `allowOutsideClick: false`, spinner saat submit form `.js-ai-processing-form`

---

## 3. Kerangka Layout

### 3.1 Tamu (belum login)

- Header tipis: nama aplikasi (`config('app.name')`).
- Konten terpusat vertikal (`flex min-h-screen`).
- Halaman: **Login** saja (route `/login`).

### 3.2 Terautentikasi

```
┌─────────────────────────────────────────────────────────┐
│ Sidebar (fixed width)  │  Main (scroll)                 │
│ - Logo → Dasbor        │  @yield('content')             │
│ - Asesmen              │  max-w-6xl                     │
│ - Master (details)     │                                │
│ - User + Keluar        │                                │
└─────────────────────────────────────────────────────────┘
```

- Submenu **Master data** memakai elemen `<details>` (buka otomatis jika route `master.*`, impor CSV, atau log).
- Breadcrumb ringan: tautan `← Kembali` / `← Master data` di atas judul (bukan komponen breadcrumb penuh).

### 3.3 Judul halaman

Setiap view mengisi `@section('title', '... — '.config('app.name'))` untuk `<title>` browser.

---

## 4. Peta Layar (Screen Inventory)

| ID | Route / nama | Peran | Prioritas |
|----|----------------|-------|-----------|
| UI-01 | Login | Tamu | P0 |
| UI-02 | Dasbor | Admin, Konsultan | P1 (placeholder) |
| UI-03 | Master — ringkasan | Admin, Konsultan | P0 |
| UI-04 | Kelompok kompetensi (list + CRUD) | Lihat: semua; Tulis: admin | P0 |
| UI-05 | Kompetensi (list + CRUD) | idem | P0 |
| UI-06 | Tingkat kompetensi (list + CRUD) | idem | P0 |
| UI-07 | Alat penilaian (list + CRUD) | idem | P0 |
| UI-08 | Versi matriks (list + CRUD + tautan pemetaan) | idem | P0 |
| UI-09 | Pemetaan kompetensi–alat (kisi) | Edit: admin; Baca: konsultan | P0 |
| UI-10 | Peserta (list + CRUD) | Tulis: admin | P0 |
| UI-11 | Impor peserta CSV | Admin | P0 |
| UI-12 | Log aktivitas | Admin | P1 |
| UI-13 | Log AI (+ filter) | Admin | P1 |
| UI-14 | Daftar asesmen | Admin, Konsultan | P0 |
| UI-15 | Buat asesmen | Admin, Konsultan | P0 |
| UI-16 | Detail asesmen | Admin, Konsultan | P0 |
| UI-17 | Ubah perilaku kunci | Admin, Konsultan | P0 |

---

## 5. Persyaratan per Layar

### UI-01 — Login

| Aspek | Persyaratan |
|-------|-------------|
| Konten | Email, kata sandi, checkbox «Ingat saya», tombol «Masuk» full-width |
| Validasi | Error via SweetAlert (validasi) atau flash |
| Aksesibilitas | `autofocus` pada email; label terasosiasi `for`/`id` |
| Responsif | Kartu `max-w-md` terpusat |

### UI-02 — Dasbor

| Aspek | Persyaratan |
|-------|-------------|
| Saat ini | Menampilkan nama pengguna dan peran; teks placeholder fase berikutnya |
| **Rencana UI** | Widget: jumlah asesmen aktif, progres finalisasi, shortcut «Buat asesmen», (fase 4+) kartu GAP & Job Fit |

### UI-03 — Master ringkasan

| Aspek | Persyaratan |
|-------|-------------|
| Layout | Grid kartu tautan (`sm:grid-cols-2`), satu kartu per modul master |
| Admin | Kartu tambahan: Log aktivitas, Log AI, Impor CSV |
| Copy | Satu kalimat per kartu; versi matriks menyebut «Termasuk pemetaan» |

### UI-04 s.d. UI-07 — CRUD master (pola umum)

| Elemen | Spesifikasi |
|--------|-------------|
| List | Tabel + paginasi Laravel; tombol «Tambah» kanan atas (admin) |
| Baris | Hover; kolom aksi: «Ubah» + «Hapus» (konfirmasi `confirm()` native) |
| Form create/edit | Kartu `max-w-lg`, field sesuai model, tombol Simpan + Batal |
| Konsultan | Sembunyikan tombol Tambah/Ubah/Hapus; tabel tanpa kolom aksi |

**Field khusus (referensi):**

- **Kompetensi:** kelompok, kode, nama, definisi, tingkat maksimum, aktif.
- **Tingkat:** kompetensi, level, indikator perilaku (textarea).
- **Alat:** kode, nama, deskripsi, urutan, aktif.
- **Versi matriks:** kode versi, nama, kunci kamus, catatan, aktif, bawaan.

### UI-09 — Pemetaan kompetensi–alat

| Aspek | Persyaratan |
|-------|-------------|
| Kisi | Baris = kompetensi (dikelompokkan INT/MNJ/LDR); kolom = alat aktif |
| Sel admin | Checkbox `name="sel[]"` value `{id_kompetensi}-{id_alat}` |
| Sel konsultan | Ikon ✓ atau kosong (read-only) |
| Sticky | Kolom «Kompetensi» `sticky left-0 z-10` |
| Simpan | Tombol «Simpan pemetaan»; checkbox konfirmasi **hapus semua** wajib jika mengosongkan seluruh kisi |
| Empty state | Banner amber jika tidak ada kompetensi/alat/kelompok |

### UI-11 — Impor peserta CSV

| Aspek | Persyaratan |
|-------|-------------|
| Bantuan | Daftar kolom wajib/opsional + 2 tautan unduh template (`,` dan `;`) |
| Form | Radio delimiter, file input `accept=".csv,.txt"`, submit «Unggah dan proses» |
| Feedback | SweetAlert / flash jumlah berhasil & gagal |

### UI-12 — Log aktivitas

| Kolom | Waktu, Pengguna, Aksi (monospace), Subjek (class + #id), IP |
| Empty | «Belum ada entri log.» |

### UI-13 — Log AI

| Aspek | Persyaratan |
|-------|-------------|
| Header | Badge jumlah failed jobs antrean |
| Filter | GET: status (berhasil/gagal), jalur (dinamis dari data) |
| Tabel | Waktu, jalur, status (badge hijau/merah), model, pengguna, latency, usage token, pesan error |
| Empty | «Belum ada log AI.» |

### UI-14 — Daftar asesmen

| Kolom | Peserta, Matriks, Tujuan (label manusia), Status, tautan Detail |
| Aksi | Tombol «Buat asesmen» kanan atas |
| Empty | «Belum ada asesmen…» + arahkan ke tombol buat |

### UI-15 — Buat asesmen

| Field | Tipe | Catatan |
|-------|------|---------|
| Peserta | Select | Hanya peserta `aktif` |
| Versi matriks | Select | Hanya versi `aktif` |
| Tujuan | Select | Promosi / Pemetaan talenta |
| Metode bukti | Radio card | 2 opsi dengan judul + deskripsi singkat |
| Tanpa INTRAY | Checkbox | Label contoh BOD-3 |
| Asesor | Checkbox list | Admin + konsultan, scroll `max-h-48` |

Submit → redirect ke detail asesmen + flash sukses.

### UI-16 — Detail asesmen (layar inti)

Halaman terpanjang; urutan section **wajib** dari atas ke bawah:

#### A. Header

- Tautan kembali ke daftar.
- Judul: nama peserta.
- Meta: kode matriks, tujuan, badge «Tanpa INTRAY» jika aktif.

#### B. Status & finalisasi

- Badge status (Draf / Berlangsung / Terintegrasi / Selesai final).
- Tombol **Finalisasi** (emerald) jika belum final dan user boleh `update`.
- Tombol **Batalkan finalisasi** (rose) hanya **admin** + status final.
- Blok **cakupan kompetensi wajib**: hitungan terpenuhi/total; daftar kompetensi kurang (amber) atau pesan hijau jika lengkap.

#### C. Metode koleksi bukti (editable)

- Panel hijau (`border-emerald-200`).
- Radio: Manual vs Otomatis; tombol «Simpan metode».
- Menentukan section mana yang ditampilkan di bawah (manual **ata** payload, tidak keduanya sekaligus untuk form input).

#### D. Asesor

- Daftar bullet nama asesor atau teks kosong.

#### E. Alat penilaian (preset) — `<details open>`

- Kisi baca-only: baris kompetensi × kolom alat preset.
- Header kolom: kode alat + badge «Preset wajib» / «Preset ops.» / «Nonaktif».
- Sel: ✓ wajib/opsional di matriks atau «—».
- Legenda di bawah kisi.

#### F. Bukti penilaian *(hanya metode manual)*

- Daftar kartu bukti: alat · kompetensi, teks `whitespace-pre-wrap`.
- Per kartu: tombol **Analisis AI** jika `config('ai.aktif')` dan user boleh update.
- Blok **Hasil AI** (violet): tingkat, keyakinan, alasan, kutipan, konfirmatori, waktu.
- Form tambah: select alat (hanya yang ada di matriks), select kompetensi, **textarea teks mentah** → Quill WYSIWYG + preview normalisasi (`data-normalize-preview="1"`).

#### G. Payload alat *(hanya metode payload_alat)*

- Form: select alat, textarea muatan (Quill + normalisasi).
- Daftar payload: id, alat, cuplikan teks, tombol **Analisis AI bulk**.
- Setelah proses: tabel usulan AI (kolom: kompetensi, level, kutipan, ringkasan, alasan, konfirmatori, keyakinan, teks PK).

#### H. Perilaku kunci (selalu tampil)

- Tabel lebar scroll: alat, kompetensi, level, teks, alasan, kutipan, aksi Edit.
- Form tambah manual di bawah (alat, kompetensi, bukti opsional, tingkat opsional, teks, alasan, kutipan).

**Perilaku saat final:**

- Form input dan tombol AI disembunyikan atau dinonaktifkan via `@can('update')` dan status (implementasi: pastikan konsisten di QA).

### UI-17 — Ubah perilaku kunci

| Field | Perilaku |
|-------|----------|
| Tingkat indikator | Select; onChange mengisi ulang teks dari indikator resmi (script inline) |
| Teks perilaku | Textarea wajib |
| Alasan pemilihan | Textarea opsional |
| Kutipan referensi | Textarea opsional |
| Aksi | Simpan (primer) + Batal |

---

## 6. Editor Teks (Quill)

Berlaku untuk textarea yang **tidak** memiliki `data-no-wysiwyg` dan tidak `disabled`.

| Persyaratan | Detail |
|-------------|--------|
| Inisialisasi | Otomatis di `DOMContentLoaded` (`resources/js/app.js`) |
| Toolbar | Header, bold/italic/underline, list, blockquote, code, clear |
| Submit | Menyimpan `teks_*`, `teks_*_rich`, `teks_*_normalized` |
| Preview | Jika `data-normalize-preview="1"`: modal preview sebelum submit final |
| Validasi kosong | SweetAlert «Input wajib diisi» + fokus ke editor |
| Pengecualian | Textarea di form perilaku kunci manual (tanpa preview) tetap bisa Quill kecuali diberi `data-no-wysiwyg` di masa depan |

---

## 7. Pola Interaksi AI di UI

| Mode | Trigger | Feedback UI |
|------|---------|-------------|
| Inkremental | Submit form `.js-ai-processing-form[data-ai-mode="incremental"]` | SweetAlert loading «Memproses AI» |
| Bulk | `data-ai-mode="bulk"` | Loading «Memproses AI bulk» |
| Nonaktif | `config('ai.aktif') === false` | Tombol AI tidak dirender |
| Hasil | Redirect + flash / blok violet di kartu bukti atau tabel payload | |
| Review | Asesor wajib buka **Edit** perilaku kunci untuk koreksi kutipan/alasan | |

**Copy wajib di UI:** hasil AI adalah **usulan**; keputusan resmi setelah review asesor.

---

## 8. Matriks Visibilitas (RBAC × UI)

| Elemen UI | Admin | Konsultan | Tamu |
|-----------|:-----:|:---------:|:----:|
| Login | — | — | ✓ |
| Dasbor, Asesmen | ✓ | ✓ | — |
| Master lihat | ✓ | ✓ | — |
| Master tulis / hapus | ✓ | — | — |
| Pemetaan edit | ✓ | — | — |
| Impor CSV, Log aktivitas, Log AI | ✓ | — | — |
| Buat/edit asesmen, bukti, AI | ✓ | ✓ | — |
| Batalkan finalisasi | ✓ | — | — |

---

## 9. Responsif & Aksesibilitas

### 9.1 Responsif

| Breakpoint | Perilaku |
|------------|----------|
| Mobile | Sidebar tetap lebar tetap; main scroll horizontal pada tabel lebar |
| `sm:` | Grid 2 kolom pada radio metode bukti, filter log AI |
| Tabel | `overflow-x-auto` wajib pada kisi matriks & tabel PK |

### 9.2 Aksesibilitas (minimum)

| Item | Status / target |
|------|-----------------|
| Bahasa HTML | `lang` dari `app()->getLocale()` |
| Navigasi | `aria-label` pada sidebar & nav utama |
| Kisi checkbox | `sr-only` teks «{kode kompetensi} — {kode alat}» |
| Details/summary | Ikon panah; `marker:content-none` — pastikan keyboard dapat membuka `<details>` |
| Konfirmasi hapus | Native `confirm()` — **rencana:** ganti SweetAlert konfirmasi untuk konsistensi |
| Fokus | Focus ring pada input (`focus:ring-1`) |
| Kontras | Teks zinc-900 di atas putih/zinc-50 memenuhi AA untuk body |

---

## 10. Status & Badge (kamus visual)

| Konteks | Tampilan |
|---------|----------|
| Status asesmen Draf | Label abu «Draf» |
| Selesai final | «Selesai (final)» / «Selesai final» di detail |
| Tanpa INTRAY | `rounded bg-amber-100 px-1.5` |
| Preset alat wajib | `bg-rose-50` «Preset wajib» |
| Matriks wajib | Lingkaran rose ✓ |
| Matriks opsional | Lingkaran zinc ✓ |
| AI berhasil (log) | `bg-emerald-100` |
| AI gagal (log) | `bg-rose-100` |

---

## 11. Empty & Error States

| Situasi | Pola UI |
|---------|---------|
| Tabel kosong | Satu baris `colspan`, teks zinc-500 terpusat, arahan tindakan |
| Tidak ada alat di matriks | Select disabled + opsi penjelasan; tombol submit disabled |
| Validasi server | SweetAlert daftar error (bullet) |
| Flash sukses | SweetAlert hijau «Berhasil» + teks `session('status')` |
| Flash error | SweetAlert merah |
| Finalisasi gagal | Error bagian finalisasi + daftar kompetensi kurang tetap terlihat di section status |

---

## 12. Persyaratan UI — Fase Mendatang

Fitur backend belum lengkap; UI berikut perlu didesain sebelum implementasi:

| Fitur | Kebutuhan UI |
|-------|----------------|
| **Dasbor GAP & Job Fit** | Kartu metrik, filter per asesmen/peserta, grafik atau heatmap (disarankan komponen chart konsisten) |
| **Integrasi skor** | Panel pratinjau bobot per alat; bedakan «pratinjau» vs «final» secara visual |
| **Laporan PDF** | Halaman pratinjau laporan + tombol export; template print-friendly |
| **Wizard asesmen lanjutan** | Stepper: peserta → kamus → job target → ringkasan alat |
| **Notifikasi real-time** | Opsional: toast non-blocking menggantikan hanya SweetAlert pasca-redirect |
| **Dark mode** | Belum ada; jika ditambah, perlu perluas token warna |
| **Mobile nav** | Hamburger collapse sidebar untuk layar sempit |

---

## 13. Kriteria Penerimaan UI (Acceptance)

### Global

- [ ] Semua halaman P0 dapat diakses sesuai peran tanpa elemen aksi yang salah peran.
- [ ] Flash dan validasi memicu SweetAlert sesuai jenis (sukses / error / validasi).
- [ ] Kisi matriks dapat di-scroll horizontal; kolom kompetensi tetap terlihat (sticky).
- [ ] Font dan warna mengikuti token §2.

### Asesmen

- [ ] Metode manual hanya menampilkan section bukti; metode payload hanya section payload.
- [ ] Tombol AI hanya tampil jika AI aktif dan asesmen belum final.
- [ ] Ringkasan cakupan wajib memperbarui setelah tambah PK dengan tingkat terisi.
- [ ] Finalisasi menampilkan error jelas jika kompetensi wajib belum lengkap.

### Master

- [ ] Admin dapat CRUD; konsultan tidak melihat kontrol tulis.
- [ ] Hapus master meminta konfirmasi.
- [ ] Pemetaan: simpan tanpa centang apa pun ditolak kecuali checkbox hapus semua dicentang.

### Aksesibilitas & responsif

- [ ] Halaman dapat digunakan pada lebar 360px (scroll horizontal diterima untuk tabel).
- [ ] Semua input form memiliki `<label>` terhubung.

---

## 14. Referensi File View

| Area | Path |
|------|------|
| Layout | `resources/views/layouts/app.blade.php` |
| Login | `resources/views/auth/login.blade.php` |
| Asesmen detail | `resources/views/assessments/show.blade.php` |
| Kisi preset | `resources/views/assessments/partials/preset-competency-tool-matrix-tbody.blade.php` |
| Pemetaan master | `resources/views/master/matrix-version-mappings.blade.php` |
| JS global UI | `resources/js/app.js` |
| CSS / theme | `resources/css/app.css` |

---

*Dokumen persyaratan UI — diselaraskan dengan implementasi AIS LPP 2026. Perbarui saat menambah layar atau mengubah design system.*
