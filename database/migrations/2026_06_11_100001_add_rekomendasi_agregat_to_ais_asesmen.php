<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->foreignId('id_revisi_konfigurasi_terakhir')
                ->nullable()
                ->after('integrasi_pratinjau_pada')
                ->constrained('ais_revisi_konfigurasi_rekomendasi')
                ->nullOnDelete();
            $table->string('kode_rekomendasi_agregat', 32)
                ->nullable()
                ->after('id_revisi_konfigurasi_terakhir')
                ->comment('qualified | not_qualified');
            $table->json('detail_rekomendasi_agregat')
                ->nullable()
                ->after('kode_rekomendasi_agregat');
        });
    }

    public function down(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->dropForeign(['id_revisi_konfigurasi_terakhir']);
            $table->dropColumn([
                'id_revisi_konfigurasi_terakhir',
                'kode_rekomendasi_agregat',
                'detail_rekomendasi_agregat',
            ]);
        });
    }
};
