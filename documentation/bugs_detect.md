# Deteksi potensi bug & rekomendasi perbaikan

**Lingkup:** audit statis kode aplikasi Laravel (controllers, Form Request, policies, layanan AI, impor CSV, rute).  
**Tanggal:** 13 Mei 2026  
**Catatan:** ini daftar risiko berdasarkan pembacaan kode; verifikasi dengan tes otomatis, review peer, dan pengujian integrasi tetap disarankan.

---

## Ringkasan prioritas

| Prioritas | Jumlah | Fokus |
|-----------|--------|--------|
| Tinggi | 3 | Integritas data asesmen setelah finalisasi, validasi bukti manual, keamanan login |
| Sedang | 5 | Race finalisasi, impor CSV besar, AI vs pemetaan matriks, konsistensi `peran`/`role`, Blade |
| Rendah | 2 | Utang teknis accessor deprecated, batas ukuran field |

---

## Temuan detail

### BUG-01 — Mutasi asesmen tetap diizinkan setelah status final (tinggi)

**Area:** `AssessmentController` (`storeEvidence`, `storeToolPayload`, `storeKeyBehavior`, `updateKeyBehavior`, `updateEvidenceCollectionMode`, `analyzeEvidenceAi`, `analyzeToolPayloadAi`) + Form Request terkait.

**Deskripsi:** `finalize()` mengubah `status` ke `SelesaiFinal`, tetapi tidak ada penghalang di jalur mutasi di atas. Pengguna dengan `can('update', $asesmen)` (admin/konsultan) masih dapat menambah bukti, payload, mengubah metode koleksi, menganalisis AI, atau mengubah perilaku kunci.

**Dampak:** Laporan asesmen “final” bisa berubah tanpa jejak kebijakan yang jelas; inkonsisten dengan ekspektasi bisnis “terkunci setelah final”.

**Solusi yang disarankan:**

1. Tambahkan aturan bisnis terpusat, misalnya method `Assessment::isFinal(): bool` atau enum check.
2. Di setiap Form Request / awal controller mutasi: jika `SelesaiFinal`, tolak dengan 403 atau redirect + error (kecuali Anda sengaja mengizinkan “koreksi admin” — jika ya, dokumentasikan dan batasi hanya peran admin).
3. Opsional: sembunyikan/nonaktifkan form di `assessments/show.blade.php` ketika final.

---

### BUG-02 — Bukti manual (`StoreEvidenceRequest`) tidak memvalidasi pasangan kompetensi–alat pada matriks (tinggi)

**Area:** `app/Http/Requests/StoreEvidenceRequest.php` vs `StoreKeyBehaviorRequest.php`.

**Deskripsi:** `StoreKeyBehaviorRequest` memvalidasi pasangan `(id_versi_matriks, id_alat_penilaian, id_kompetensi)` lewat `CompetencyToolMapping`. `StoreEvidenceRequest` hanya memastikan alat ada di pemilihan asesmen dan dipakai matriks untuk alat tersebut, **tanpa** memastikan kompetensi yang dipilih dipetakan ke alat itu.

**Dampak:** Data bukti dapat mereferensikan kombinasi kompetensi + alat yang tidak ada di kisi pemetaan versi matriks asesmen; downstream (analisis, laporan, finalisasi) bisa ambigu.

**Solusi:** Di `withValidator` `StoreEvidenceRequest`, tambahkan pengecekan identik dengan blok `pasanganMapped` di `StoreKeyBehaviorRequest` (sama `id_kompetensi` + `id_alat_penilaian` + `id_versi_matriks` + aturan `aktif`).

---

### BUG-03 — Endpoint login tanpa rate limiting (tinggi)

**Area:** `routes/web.php` (`login.store`), `LoginController::store`.

**Deskripsi:** Rute POST login tidak memakai middleware `throttle`, sehingga percobaan kata sandi dapat dilakukan secara massal dari klien.

**Dampak:** Risiko credential stuffing / brute force pada instalasi yang terpapar internet.

**Solusi:**

```php
Route::post('login', [LoginController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');
```

Sesuaikan angka (mis. 5 percobaan per menit per IP) dan pertimbangkan `ThrottleRequests` kustom per `email` jika diperlukan.

---

### BUG-04 — Race condition pada finalisasi (sedang)

**Area:** `AssessmentController::finalize`.

**Deskripsi:** Alur membaca `ringkasanCakupanKompetensiWajib` lalu `update` status dilakukan tanpa transaksi DB dengan kunci baris. Dua permintaan paralel bisa lolos pengecekan yang sama sebelum keduanya menulis status final.

**Dampak:** Duplikasi aktivitas log, atau (lebih jarang) keadaan tidak terduga jika nanti ada efek samping tambahan di finalisasi.

**Solusi:** Bungkus dalam `DB::transaction` dan gunakan `Assessment::query()->whereKey($id)->where('status', AssessmentStatus::Draf)->lockForUpdate()->first()` lalu validasi ulang dan update; atau `update` dengan klausa `where('status', ...)` dan cek `affected rows === 1`.

---

### BUG-05 — Impor CSV: satu transaksi untuk seluruh berkas (sedang)

**Area:** `ParticipantImportController::store`.

**Deskripsi:** Seluruh loop `updateOrCreate` berjalan di dalam **satu** `DB::transaction`. Berkas besar → transaksi panjang → risiko timeout koneksi DB, kunci tabel lebih lama, dan penggunaan memori.

**Dampak:** Kegagalan impor di produksi saat data banyak; pengalaman pengguna buruk.

**Solusi:** Pecah menjadi chunk (mis. setiap N baris commit), atau `DB::transaction` per baris dengan trade-off performa; tambahkan batas jumlah baris maksimum yang dapat diproses per unggahan.

---

### BUG-06 — Impor CSV: ukuran berkas dibatasi, baris tidak dibatasi (sedang)

**Area:** `ImportParticipantsCsvRequest` (`max:5120` ≈ 5 MB) + `ParticipantImportController::store`.

**Deskripsi:** Unggahan dibatasi per ukuran file, tetapi loop tidak membatasi **jumlah baris**. CSV padat dalam 5 MB masih bisa berisi puluhan ribu baris dan memicu transaksi panjang (lihat BUG-05).

**Dampak:** Beban CPU/DB tinggi dan risiko timeout meski di bawah batas MB.

**Solusi:** Tambahkan batas baris maksimum di loop (mis. hentikan dengan error setelah 5.000–10.000 baris) dan/atau chunking transaksi.

---

### BUG-07 — Analisis AI bulk: kompetensi yang diizinkan model tidak dibatasi ke pemetaan matriks untuk alat tersebut (sedang)

**Area:** `BulkToolPayloadAiAnalyzer::analisisPayload` — daftar kompetensi diambil dari `Competency::query()->where('aktif', true)` (global), bukan dari `CompetencyToolMapping` untuk `id_versi_matriks` + `id_alat_penilaian` payload.

**Dampak:** Model dapat mengusulkan kode kompetensi yang valid secara global tetapi **tidak** termasuk pemetaan alat pada matriks asesmen; perilaku kunci bisa terbentuk di luar rancangan matriks (tergantung apakah ada filter tambahan saat simpan — saat ini `firstOrNew` per asesmen+alat+kompetensi tetap bisa membuat baris untuk kompetensi di luar kisi).

**Solusi:** Bangun daftar kode kompetensi yang diperbolehkan dari `CompetencyToolMapping` untuk versi matriks asesmen dan alat payload; injeksikan itu ke prompt; setelah parse, buang usulan yang kode-nya tidak ada di set tersebut.

---

### BUG-08 — Inkonsistensi penamaan atribut `peran` vs accessor `role` (sedang / utang teknis)

**Area:** `User` model (`peran` di DB, accessor deprecated `role()`), `AssessmentPolicy`, `EnsureUserHasRole`, banyak Form Request master memakai `$user->role`.

**Deskripsi:** Saat ini accessor menjembatani ke `peran`, sehingga perilaku benar. Jika accessor dihapus di masa depan, otorisasi bisa diam-diam rusak.

**Solusi:** Secara bertahap ganti ke `$user->peran` di policy/middleware/request, atau gunakan method eksplisit `User::hasPeran(string ...$roles): bool` dan hapus ketergantungan pada `role` deprecated.

---

### BUG-09 — `UpdateKeyBehaviorRequest`: rule `exists` tanpa filter soft delete (sedang)

**Area:** `UpdateKeyBehaviorRequest` — `'id_tingkat_kompetensi' => ['nullable', 'integer', 'exists:ais_tingkat_kompetensi,id']`.

**Deskripsi:** Validator `after` memang memakai `whereNull('dihapus_pada')`, tetapi rule `exists` masih bisa menerima ID tingkat yang sudah dihapus logis jika input dimanipulasi sebelum `after` (urutan validasi Laravel tetap menjalankan `exists` dulu).

**Solusi:** Ganti `exists` dengan `Rule::exists(...)->whereNull('dihapus_pada')` agar konsisten dengan `StoreKeyBehaviorRequest`.

---

### BUG-10 — Blade: directive di dalam tag komponen (rendah / sudah ditangani)

**Area:** `resources/views/assessments/show.blade.php`.

**Deskripsi:** Penggunaan `@disabled(...)` pada tag `<x-ui.btn>` atau atribut kompleks lain pernah memicu `ParseError` (token `endif` tidak sejajar).

**Solusi:** Hindari `@disabled` di baris pembuka komponen; gunakan `@if (...) disabled @endif` atau atribut HTML dinamis. **Status:** pola pengganti sebaiknya sudah diterapkan di cabang terkini; setelah deploy jalankan `php artisan view:clear`.

---

## Hal positif yang sudah ada (mitigasi)

- **Throttle** pada pemicu analisis AI (`throttle:ai-analysis-trigger`) mengurangi penyalahgunaan API eksternal.
- **Pembatalan finalisasi** dibatasi admin (`unfinalize` + `abort_unless` pada `peran`).
- **`StoreAssessmentToolPayloadRequest`** dan **`StoreEvidenceRequest`** memvalidasi alat terhadap pemilihan asesmen + keberadaan di pemetaan matriks (untuk alat).
- **`abort_unless`** pada bukti/payload/perilaku memastikan ID milik asesmen yang sama pada beberapa aksi.

---

## Saran pengujian regresi (singkat)

1. Finalisasi asesmen → coba POST bukti / payload / ubah metode → harus ditolak (setelah BUG-01 diperbaiki).
2. POST bukti dengan `id_kompetensi` yang tidak dipetakan ke alat pada matriks → harus gagal validasi (setelah BUG-02).
3. Beberapa klik cepat “Finalisasi” paralel → hanya satu transisi sah (setelah BUG-04).
4. Impor CSV > N ribu baris → batas atau chunk jelas (BUG-05/06).
5. Login salah berulang → throttling aktif (BUG-03).

---

## Referensi file (untuk navigasi cepat)

- `app/Http/Controllers/AssessmentController.php`
- `app/Http/Requests/StoreEvidenceRequest.php`
- `app/Http/Requests/StoreKeyBehaviorRequest.php`
- `app/Http/Controllers/ParticipantImportController.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `routes/web.php`
- `app/Services/Ai/BulkToolPayloadAiAnalyzer.php`
- `app/Policies/AssessmentPolicy.php` & `app/Models/User.php`
