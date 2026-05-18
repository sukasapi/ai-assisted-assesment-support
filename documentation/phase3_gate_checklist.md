# Gate Checklist Phase 3 (AI Integration)

Dokumen ini menjadi acuan objektif untuk menyatakan Phase 3 selesai.

## A. Acceptance Gate (wajib PASS)

1. Jalur AI incremental dan bulk berjalan lewat queue (bukan blocking request).
2. Retry, timeout, dan backoff job AI terdefinisi.
3. Endpoint trigger AI memakai throttle/rate-limit.
4. Parser AI menerapkan validasi schema wajib dan fail-fast yang jelas.
5. Validasi kutipan verbatim terhadap teks sumber aktif.
6. Log AI menyimpan jalur, model, latency, usage, status, dan error.
7. Monitoring log AI tersedia untuk admin.
8. UAT asesor untuk 2 jalur (incremental + bulk) lulus.

## B. Test Case Teknis (otomasi)

1. Trigger incremental mendorong job ke queue AI.
2. Trigger bulk mendorong job ke queue AI.
3. Respons incremental dengan field wajib tidak lengkap menghasilkan gagal terarah.
4. Respons bulk dengan field wajib tidak lengkap menghasilkan gagal terarah.
5. Log AI tetap tercatat saat gagal parse/validasi.
6. Deduplikasi kompetensi per tool tetap konsisten setelah proses bulk.

## C. UAT Asesor (manual)

1. **Incremental**
   - Input bukti mentah.
   - Klik analisis AI.
   - Verifikasi: tingkat usulan, alasan, keyakinan, dan kutipan tampil.
   - Verifikasi: kutipan relevan terhadap bukti.

2. **Bulk**
   - Input payload alat.
   - Jalankan analisis AI bulk.
   - Verifikasi: tabel usulan AI berisi tingkat, alasan, kutipan, keyakinan.
   - Verifikasi: tidak ada kompetensi ganda pada tool yang sama.
   - Verifikasi: perilaku kunci terbentuk dan bisa diedit.

3. **Kualitas & Audit**
   - Verifikasi admin dapat melihat log AI.
   - Verifikasi entri gagal menampilkan pesan kesalahan yang bisa ditindaklanjuti.

## D. Kriteria Sign-off

Phase 3 dapat ditutup bila:

- Semua gate bagian A berstatus PASS.
- Semua test case teknis bagian B PASS.
- UAT bagian C tidak menyisakan isu kritis.
