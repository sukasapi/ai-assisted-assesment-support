<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_payload_alat_asesmen', function (Blueprint $table) {
            $table->string('status_analisis', 32)->default('belum')->after('diproses_pada');
            $table->text('pesan_status_analisis')->nullable()->after('status_analisis');
            $table->timestamp('dijadwalkan_pada')->nullable()->after('pesan_status_analisis');
        });

        DB::table('ais_payload_alat_asesmen')
            ->whereNotNull('diproses_pada')
            ->whereNotNull('hasil_analisis_ai')
            ->update(['status_analisis' => 'berhasil']);
    }

    public function down(): void
    {
        Schema::table('ais_payload_alat_asesmen', function (Blueprint $table) {
            $table->dropColumn(['status_analisis', 'pesan_status_analisis', 'dijadwalkan_pada']);
        });
    }
};
