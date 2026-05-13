<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_perilaku_kunci', function (Blueprint $table) {
            $table->text('alasan_pemilihan')->nullable()->after('teks_perilaku')->comment('Alasan pemilihan tingkat/evidence; bisa dari AI atau penyuntingan asesor');
        });
    }

    public function down(): void
    {
        Schema::table('ais_perilaku_kunci', function (Blueprint $table) {
            $table->dropColumn('alasan_pemilihan');
        });
    }
};
