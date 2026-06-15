<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_revisi_konfigurasi_rekomendasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_versi_matriks')->constrained('ais_versi_matriks')->restrictOnDelete();
            $table->unsignedInteger('nomor_revisi');
            $table->json('konfigurasi');
            $table->text('ringkasan_perubahan');
            $table->foreignId('id_pengguna_pembuat')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();

            $table->unique(['id_versi_matriks', 'nomor_revisi'], 'ais_revisi_konfig_rekom_versi_nomor_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_revisi_konfigurasi_rekomendasi');
    }
};
