<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_bukti_penilaian', function (Blueprint $table) {
            $table->timestamp('dihapus_pada')->nullable()->after('diperbarui_pada');
        });
    }

    public function down(): void
    {
        Schema::table('ais_bukti_penilaian', function (Blueprint $table) {
            $table->dropColumn('dihapus_pada');
        });
    }
};
