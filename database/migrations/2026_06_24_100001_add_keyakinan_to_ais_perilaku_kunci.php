<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_perilaku_kunci', function (Blueprint $table) {
            $table->decimal('keyakinan', 4, 3)
                ->nullable()
                ->after('kutipan_referensi')
                ->comment('Keyakinan AI 0..1 untuk usulan perilaku kunci (null untuk PK manual)');
        });
    }

    public function down(): void
    {
        Schema::table('ais_perilaku_kunci', function (Blueprint $table) {
            $table->dropColumn('keyakinan');
        });
    }
};
