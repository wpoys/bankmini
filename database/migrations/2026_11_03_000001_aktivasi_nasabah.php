<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status_aktivasi', 20)->default('aktif')->after('status');
            $table->string('kode_aktivasi_hash')->nullable();
            $table->timestamp('kode_aktivasi_kadaluarsa')->nullable();
            $table->unsignedTinyInteger('percobaan_aktivasi')->default(0);
            $table->timestamp('aktivasi_dikunci_sampai')->nullable();
            $table->timestamp('aktivasi_kirim_ulang_sampai')->nullable();
            $table->unsignedTinyInteger('jumlah_kirim_ulang')->default(0);
            $table->string('token_aktivasi_hash')->nullable();
            $table->timestamp('token_aktivasi_kadaluarsa')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'status_aktivasi', 'kode_aktivasi_hash', 'kode_aktivasi_kadaluarsa',
                'percobaan_aktivasi', 'aktivasi_dikunci_sampai',
                'aktivasi_kirim_ulang_sampai', 'jumlah_kirim_ulang',
                'token_aktivasi_hash', 'token_aktivasi_kadaluarsa',
            ]);
        });
    }
};
