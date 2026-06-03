<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ais_pengguna', function (Blueprint $table) {
            $table->boolean('aktif')->default(true)->after('peran');
        });

        Schema::create('ais_sesi_asesmen', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sesi', 64)->unique();
            $table->string('nama', 255);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status', 32)->default('draf')->comment('draf | aktif | selesai');
            $table->text('catatan')->nullable();
            $table->foreignId('id_pengguna_pembuat')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->foreignId('id_sesi_asesmen')
                ->nullable()
                ->after('id_versi_matriks')
                ->constrained('ais_sesi_asesmen')
                ->restrictOnDelete();
        });

        $legacyId = $this->pastikanSesiLegacy();
        DB::table('ais_asesmen')->whereNull('id_sesi_asesmen')->update(['id_sesi_asesmen' => $legacyId]);

        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sesi_asesmen')->nullable(false)->change();
        });

        Schema::table('ais_asesmen_asesor', function (Blueprint $table) {
            $table->string('jenis_penugasan', 16)->default('admin')->after('id_pengguna')->comment('admin');
        });

        DB::table('ais_asesmen_asesor')->update(['jenis_penugasan' => 'admin']);

        $idKonsultan = DB::table('ais_pengguna')->where('peran', 'konsultan')->pluck('id');
        if ($idKonsultan->isNotEmpty()) {
            DB::table('ais_asesmen_asesor')->whereIn('id_pengguna', $idKonsultan)->delete();
        }

        Schema::create('ais_penugasan_konsultan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pengguna')->constrained('ais_pengguna')->cascadeOnDelete();
            $table->foreignId('id_sesi_asesmen')->constrained('ais_sesi_asesmen')->cascadeOnDelete();
            $table->string('token_akses', 8)->unique();
            $table->boolean('aktif')->default(true);
            $table->timestamp('kedaluwarsa_pada')->nullable();
            $table->foreignId('id_pengguna_pembuat')->nullable()->constrained('ais_pengguna')->nullOnDelete();
            $table->timestamp('dibuat_pada')->nullable()->useCurrent();
            $table->timestamp('diperbarui_pada')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index(['id_pengguna', 'aktif']);
        });

        Schema::create('ais_penugasan_konsultan_asesmen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_penugasan_konsultan')->constrained('ais_penugasan_konsultan')->cascadeOnDelete();
            $table->foreignId('id_asesmen')->constrained('ais_asesmen')->cascadeOnDelete();
            $table->unique(['id_penugasan_konsultan', 'id_asesmen'], 'ais_penugasan_konsultan_asesmen_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ais_penugasan_konsultan_asesmen');
        Schema::dropIfExists('ais_penugasan_konsultan');

        Schema::table('ais_asesmen_asesor', function (Blueprint $table) {
            $table->dropColumn('jenis_penugasan');
        });

        Schema::table('ais_asesmen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_sesi_asesmen');
        });

        Schema::dropIfExists('ais_sesi_asesmen');

        Schema::table('ais_pengguna', function (Blueprint $table) {
            $table->dropColumn('aktif');
        });
    }

    private function pastikanSesiLegacy(): int
    {
        $existing = DB::table('ais_sesi_asesmen')->where('kode_sesi', 'SES-LEGACY')->value('id');
        if ($existing !== null) {
            return (int) $existing;
        }

        $pembuat = DB::table('ais_pengguna')->where('peran', 'admin')->value('id');

        return (int) DB::table('ais_sesi_asesmen')->insertGetId([
            'kode_sesi' => 'SES-LEGACY',
            'nama' => 'Sesi migrasi (data lama)',
            'status' => 'aktif',
            'id_pengguna_pembuat' => $pembuat,
            'dibuat_pada' => now(),
            'diperbarui_pada' => now(),
        ]);
    }
};
