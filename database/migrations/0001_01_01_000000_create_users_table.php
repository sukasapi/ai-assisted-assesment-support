<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ais_pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('alamat_surel')->unique();
            $table->timestamp('diverifikasi_pada')->nullable();
            $table->string('kata_sandi');
            $table->string('peran', 32)->default('konsultan');
            $table->string('token_ingat', 100)->nullable();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        /*
         * Kolom email, token, created_at wajib mengikuti kontrak Illuminate\Auth\Passwords\DatabaseTokenRepository.
         */
        Schema::create('ais_token_reset_kata_sandi', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        /*
         * Kolom mengikuti kontrak Illuminate\Session\DatabaseSessionHandler (id, user_id, payload, last_activity, ip_address, user_agent).
         */
        Schema::create('ais_sesi', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ais_pengguna');
        Schema::dropIfExists('ais_token_reset_kata_sandi');
        Schema::dropIfExists('ais_sesi');
    }
};
