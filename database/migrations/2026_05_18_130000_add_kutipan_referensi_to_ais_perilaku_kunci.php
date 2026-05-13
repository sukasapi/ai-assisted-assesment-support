<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_perilaku_kunci', function (Blueprint $table) {
            $table->text('kutipan_referensi')->nullable()->after('alasan_pemilihan')->comment('Kutipan verbatim dari bukti/payload (bulk AI atau penyuntingan)');
        });
    }

    public function down(): void
    {
        Schema::table('ais_perilaku_kunci', function (Blueprint $table) {
            $table->dropColumn('kutipan_referensi');
        });
    }
};
