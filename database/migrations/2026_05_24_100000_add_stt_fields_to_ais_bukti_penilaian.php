<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_bukti_penilaian', function (Blueprint $table) {
            $table->string('jenis_sumber', 32)->default('teks')->after('id_kompetensi');
            $table->string('path_audio', 512)->nullable()->after('teks_mentah_normalized');
            $table->string('mime_audio', 128)->nullable()->after('path_audio');
            $table->unsignedInteger('durasi_audio_detik')->nullable()->after('mime_audio');
            $table->string('status_transkripsi', 32)->nullable()->after('durasi_audio_detik');
            $table->text('pesan_status_transkripsi')->nullable()->after('status_transkripsi');
        });
    }

    public function down(): void
    {
        Schema::table('ais_bukti_penilaian', function (Blueprint $table) {
            $table->dropColumn([
                'jenis_sumber',
                'path_audio',
                'mime_audio',
                'durasi_audio_detik',
                'status_transkripsi',
                'pesan_status_transkripsi',
            ]);
        });
    }
};
