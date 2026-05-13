<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'ais_kelompok_kompetensi',
            'ais_kompetensi',
            'ais_tingkat_kompetensi',
            'ais_alat_penilaian',
            'ais_versi_matriks',
            'ais_pemetaan_kompetensi_alat',
            'ais_peserta',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->timestamp('dihapus_pada')->nullable()->after('diperbarui_pada');
                $table->index('dihapus_pada', $tableName.'_dihapus_pada_idx');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'ais_kelompok_kompetensi',
            'ais_kompetensi',
            'ais_tingkat_kompetensi',
            'ais_alat_penilaian',
            'ais_versi_matriks',
            'ais_pemetaan_kompetensi_alat',
            'ais_peserta',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName.'_dihapus_pada_idx');
                $table->dropColumn('dihapus_pada');
            });
        }
    }
};
