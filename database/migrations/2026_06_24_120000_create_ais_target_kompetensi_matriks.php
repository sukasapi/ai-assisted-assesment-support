<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Target tingkat per kompetensi per versi matriks (profil jabatan tujuan).
     * Bila diisi, dipakai sebagai target alih-alih heuristik (promosi +1 / talenta 4).
     * Dibekukan ke snapshot saat asesmen dibuat.
     */
    public function up(): void
    {
        Schema::create('ais_target_kompetensi_matriks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_versi_matriks')->constrained('ais_versi_matriks')->cascadeOnDelete();
            $table->unsignedBigInteger('id_kompetensi');
            $table->unsignedTinyInteger('tingkat_target');
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->unique(['id_versi_matriks', 'id_kompetensi'], 'uniq_target_matriks_komp');
        });

        Schema::table('ais_snapshot_pemetaan_asesmen', function (Blueprint $table) {
            $table->unsignedTinyInteger('tingkat_target')->nullable()->after('bobot')
                ->comment('Target jabatan beku per kompetensi (null = pakai heuristik)');
        });
    }

    public function down(): void
    {
        Schema::table('ais_snapshot_pemetaan_asesmen', function (Blueprint $table) {
            $table->dropColumn('tingkat_target');
        });
        Schema::dropIfExists('ais_target_kompetensi_matriks');
    }
};
