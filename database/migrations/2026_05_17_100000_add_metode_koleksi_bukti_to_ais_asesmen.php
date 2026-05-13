<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->string('metode_koleksi_bukti', 32)
                ->default('manual')
                ->after('tanpa_intray')
                ->comment('manual | payload_alat');
        });
    }

    public function down(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->dropColumn('metode_koleksi_bukti');
        });
    }
};
