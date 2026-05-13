<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_kelompok_kompetensi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 16)->unique();
            $table->string('nama');
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_kompetensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kelompok_kompetensi')->constrained('ais_kelompok_kompetensi')->restrictOnDelete();
            $table->string('kode_kompetensi', 32)->unique();
            $table->string('nama');
            $table->text('definisi')->nullable();
            $table->unsignedTinyInteger('tingkat_maksimum')->default(6);
            $table->boolean('aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_tingkat_kompetensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_kompetensi')->constrained('ais_kompetensi')->cascadeOnDelete();
            $table->unsignedTinyInteger('tingkat');
            $table->text('indikator_perilaku');
            $table->string('etiket')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id_kompetensi', 'tingkat']);
        });

        Schema::create('ais_alat_penilaian', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 32)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_versi_matriks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_versi', 64)->unique();
            $table->string('nama_versi');
            $table->string('kunci_kamus', 64)->nullable()->comment('e.g. PTPN17, PTPN11, KBUMN12, KBUMN10');
            $table->text('catatan_konteks')->nullable();
            $table->boolean('aktif')->default(true);
            $table->boolean('bawaan')->default(false);
            $table->timestamp('dipublikasikan_pada')->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_pemetaan_kompetensi_alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_versi_matriks')->constrained('ais_versi_matriks')->cascadeOnDelete();
            $table->foreignId('id_kompetensi')->constrained('ais_kompetensi')->restrictOnDelete();
            $table->foreignId('id_alat_penilaian')->constrained('ais_alat_penilaian')->restrictOnDelete();
            $table->boolean('wajib')->default(false);
            $table->decimal('bobot', 10, 4)->default(1);
            $table->boolean('aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id_versi_matriks', 'id_kompetensi', 'id_alat_penilaian'], 'ais_pemetaan_versi_kompetensi_alat_unik');
        });

        Schema::create('ais_peserta', function (Blueprint $table) {
            $table->id();
            $table->string('kode_peserta')->unique();
            $table->string('nama_lengkap');
            $table->string('alamat_surel')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('id_versi_matriks')->nullable()->constrained('ais_versi_matriks')->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('ais_log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->string('aksi', 128);
            $table->string('subjek_tipe')->nullable();
            $table->unsignedBigInteger('subjek_id')->nullable();
            $table->index(['subjek_tipe', 'subjek_id']);
            $table->json('properti')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamp('dibuat_pada')->useCurrent();

            $table->index('dibuat_pada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_log_aktivitas');
        Schema::dropIfExists('ais_peserta');
        Schema::dropIfExists('ais_pemetaan_kompetensi_alat');
        Schema::dropIfExists('ais_versi_matriks');
        Schema::dropIfExists('ais_alat_penilaian');
        Schema::dropIfExists('ais_tingkat_kompetensi');
        Schema::dropIfExists('ais_kompetensi');
        Schema::dropIfExists('ais_kelompok_kompetensi');
    }
};
