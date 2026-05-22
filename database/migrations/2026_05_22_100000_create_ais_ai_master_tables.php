<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ais_template_prompt_ai', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 64)->unique();
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->longText('teks_instruksi');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('dihapus_pada')->nullable();
            $table->index(['aktif', 'dihapus_pada']);
        });

        Schema::create('ais_model_ai', function (Blueprint $table) {
            $table->id();
            $table->string('id_model_openrouter', 191);
            $table->string('label', 255);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('utama')->default(false)->comment('Model default bila tidak dipilih di UI');
            $table->boolean('aktif')->default(true);
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('dihapus_pada')->nullable();
            $table->unique('id_model_openrouter');
            $table->index(['aktif', 'dihapus_pada']);
        });

        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->foreignId('id_template_prompt_ai')
                ->nullable()
                ->after('metode_koleksi_bukti')
                ->constrained('ais_template_prompt_ai')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_template_prompt_ai');
        });

        Schema::dropIfExists('ais_model_ai');
        Schema::dropIfExists('ais_template_prompt_ai');
    }
};
