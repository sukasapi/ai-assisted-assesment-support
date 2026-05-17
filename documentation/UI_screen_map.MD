# Peta layar UI — AIS LPP 2026

Ringkasan urutan layar dan pasangan fitur (selaras implementasi Kinetic).

| # | Layar | Route | View |
|---|-------|-------|------|
| 0 | Login | `/login` | `auth/login` |
| 1 | Dasbor | `/dashboard` | `dashboard` |
| 2 | Master hub | `/master` | `master/index` |
| 2.x | CRUD master | `/master/...` | `master/*` |
| 3 | Daftar asesmen | `/asesmen` | `assessments/index` |
| 3 | Buat / detail asesmen | `/asesmen/buat`, `/asesmen/{id}` | `assessments/create`, `show` |
| 3 | Edit PK | `/asesmen/{id}/perilaku-kunci/{id}/ubah` | `assessments/key-behaviors/edit` |
| 4 | Log / impor | admin only | `master/activity-logs`, `ai-logs`, `participants/import` |

Mockup referensi: `documentation/UIdesign/*_refined/code.html`.
