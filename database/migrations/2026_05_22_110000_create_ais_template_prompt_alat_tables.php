<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_pemetaan_template_alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_template_prompt_ai')
                ->constrained('ais_template_prompt_ai')
                ->cascadeOnDelete();
            $table->foreignId('id_alat_penilaian')
                ->constrained('ais_alat_penilaian')
                ->cascadeOnDelete();
            $table->unique(['id_template_prompt_ai', 'id_alat_penilaian'], 'ais_tpl_alat_unique');
            $table->index('id_alat_penilaian');
        });

        Schema::create('ais_asesmen_template_alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->foreignId('id_alat_penilaian')->constrained('ais_alat_penilaian')->restrictOnDelete();
            $table->string('mode', 16)->default('master')->comment('master | none | custom');
            $table->foreignId('id_template_prompt_ai')
                ->nullable()
                ->constrained('ais_template_prompt_ai')
                ->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['id_asesmen', 'id_alat_penilaian'], 'ais_asesmen_tpl_alat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_asesmen_template_alat');
        Schema::dropIfExists('ais_pemetaan_template_alat');
    }
};
