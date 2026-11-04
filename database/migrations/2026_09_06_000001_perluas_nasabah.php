<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->string('kode_jurusan', 10)->nullable()->after('no_rekening');
            $table->enum('produk', ['siswa', 'guru'])->default('siswa')->after('kelas');
            $table->string('qr_code', 255)->nullable()->after('no_rekening');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropColumn(['kode_jurusan', 'produk', 'qr_code']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'teller', 'supervisor', 'nasabah'])->change();
        });
    }
};
