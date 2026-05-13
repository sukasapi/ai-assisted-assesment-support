<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_asesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_peserta')->constrained('ais_peserta')->restrictOnDelete();
            $table->foreignId('id_versi_matriks')->constrained('ais_versi_matriks')->restrictOnDelete();
            $table->string('tujuan', 32)->comment('promosi | pemetaan_talenta');
            $table->string('status', 32)->default('draf')->comment('draf | berlangsung | terintegrasi | selesai_final');
            $table->boolean('tanpa_intray')->default(false)->comment('BOD-3: tidak memakai INTRAY');
            $table->foreignId('id_pengguna_pembuat')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->foreignId('id_pengguna_finalisasi')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->timestamp('waktu_finalisasi')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_asesmen_asesor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_pengguna')->constrained('ais_pengguna')->cascadeOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id_asesmen', 'id_pengguna']);
        });

        Schema::create('ais_pemilihan_alat_asesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_alat_penilaian')->constrained('ais_alat_penilaian')->restrictOnDelete();
            $table->boolean('wajib')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id_asesmen', 'id_alat_penilaian']);
        });

        Schema::create('ais_bukti_penilaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_alat_penilaian')->constrained('ais_alat_penilaian')->restrictOnDelete();
            $table->foreignId('id_kompetensi')->constrained('ais_kompetensi')->restrictOnDelete();
            $table->text('teks_mentah');
            $table->text('teks_kerja')->nullable();
            $table->string('ai_tingkat')->nullable();
            $table->text('ai_alasan')->nullable();
            $table->decimal('ai_keyakinan', 5, 4)->nullable();
            $table->json('ai_muatan')->nullable();
            $table->timestamp('ai_dinilai_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_perilaku_kunci', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_alat_penilaian')->constrained('ais_alat_penilaian')->restrictOnDelete();
            $table->foreignId('id_kompetensi')->constrained('ais_kompetensi')->restrictOnDelete();
            $table->foreignId('id_bukti_penilaian')->nullable()->constrained('ais_bukti_penilaian')->nullOnDelete();
            $table->foreignId('id_tingkat_kompetensi')->nullable()->constrained('ais_tingkat_kompetensi')->nullOnDelete();
            $table->text('teks_perilaku');
            $table->boolean('tervalidasi')->default(false);
            $table->foreignId('id_pengguna_validasi')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->timestamp('waktu_validasi')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_perilaku_kunci');
        Schema::dropIfExists('ais_bukti_penilaian');
        Schema::dropIfExists('ais_pemilihan_alat_asesmen');
        Schema::dropIfExists('ais_asesmen_asesor');
        Schema::dropIfExists('ais_asesmen');
    }
};
