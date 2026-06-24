<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot pemetaan kompetensi–alat per asesmen: membekukan wajib/bobot/aktif saat
     * asesmen dibuat agar perubahan matriks "hidup" tidak menggeser hasil asesmen lama.
     */
    public function up(): void
    {
        Schema::create('ais_snapshot_pemetaan_asesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->unsignedBigInteger('id_kompetensi');
            $table->unsignedBigInteger('id_alat_penilaian');
            $table->boolean('wajib')->default(false);
            $table->decimal('bobot', 10, 4)->default(0);
            $table->boolean('aktif')->nullable()->default(true);
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_asesmen', 'id_kompetensi', 'id_alat_penilaian'], 'uniq_snapshot_asesmen_komp_alat');
            $table->index(['id_asesmen', 'id_alat_penilaian']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_snapshot_pemetaan_asesmen');
    }
};
