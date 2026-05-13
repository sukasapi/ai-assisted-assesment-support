<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_payload_alat_asesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_alat_penilaian')->constrained('ais_alat_penilaian')->restrictOnDelete();
            $table->longText('teks_muatan');
            $table->foreignId('id_pengguna_pengunggah')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->json('hasil_analisis_ai')->nullable();
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_log_ai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->foreignId('id_asesmen')->nullable()->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_bukti_penilaian')->nullable()->constrained('ais_bukti_penilaian')->nullOnDelete();
            $table->foreignId('id_payload_alat_asesmen')->nullable()->constrained('ais_payload_alat_asesmen')->nullOnDelete();
            $table->string('jalur', 64)->comment('analisis_bukti_incremental | pemetaan_payload_bulk');
            $table->string('nama_model', 128)->nullable();
            $table->string('status', 32)->default('berhasil')->comment('berhasil | gagal');
            $table->unsignedSmallInteger('kode_http')->nullable();
            $table->json('metadata')->nullable();
            $table->text('pesan_kesalahan')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_log_ai');
        Schema::dropIfExists('ais_payload_alat_asesmen');
    }
};
