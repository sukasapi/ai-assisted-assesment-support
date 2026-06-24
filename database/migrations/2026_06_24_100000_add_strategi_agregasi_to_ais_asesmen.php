<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->string('strategi_agregasi_alat', 16)
                ->default('max')
                ->after('metode_koleksi_bukti')
                ->comment('max | rata_rata — cara menggabungkan beberapa PK satu alat menjadi satu tingkat');
        });
    }

    public function down(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->dropColumn('strategi_agregasi_alat');
        });
    }
};
