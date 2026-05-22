<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_integrasi_kompetensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_kompetensi')->constrained('ais_kompetensi')->restrictOnDelete();
            $table->unsignedTinyInteger('tingkat_target')->nullable();
            $table->unsignedTinyInteger('tingkat_tercapai')->nullable();
            $table->decimal('skor_terbobot', 10, 4)->nullable();
            $table->smallInteger('selisih_gap')->nullable();
            $table->string('rekomendasi_kode', 32)->nullable()->comment('fit | development | not_fit');
            $table->text('rekomendasi_teks')->nullable();
            $table->unsignedInteger('jumlah_pk_masuk')->default(0);
            $table->json('detail_bobot')->nullable();
            $table->string('sumber_utama', 32)->nullable();
            $table->string('versi_perhitungan', 16)->default('v1');
            $table->timestamp('dihitung_pada')->nullable();
            $table->foreignId('id_pengguna_pemicu')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->unique(['id_asesmen', 'id_kompetensi'], 'ais_integrasi_asesmen_kompetensi_unik');
        });

        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->decimal('job_fit_persen_pratinjau', 5, 2)->nullable()->after('catatan');
            $table->timestamp('integrasi_pratinjau_pada')->nullable()->after('job_fit_persen_pratinjau');
        });
    }

    public function down(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->dropColumn(['job_fit_persen_pratinjau', 'integrasi_pratinjau_pada']);
        });

        Schema::dropIfExists('ais_integrasi_kompetensi');
    }
};
