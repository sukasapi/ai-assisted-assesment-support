<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_bukti_penilaian', function (Blueprint $table) {
            $table->longText('teks_mentah_rich')->nullable()->after('teks_mentah');
            $table->longText('teks_mentah_normalized')->nullable()->after('teks_mentah_rich');
        });

        Schema::table('ais_payload_alat_asesmen', function (Blueprint $table) {
            $table->longText('teks_muatan_rich')->nullable()->after('teks_muatan');
            $table->longText('teks_muatan_normalized')->nullable()->after('teks_muatan_rich');
        });
    }

    public function down(): void
    {
        Schema::table('ais_bukti_penilaian', function (Blueprint $table) {
            $table->dropColumn(['teks_mentah_rich', 'teks_mentah_normalized']);
        });

        Schema::table('ais_payload_alat_asesmen', function (Blueprint $table) {
            $table->dropColumn(['teks_muatan_rich', 'teks_muatan_normalized']);
        });
    }
};
