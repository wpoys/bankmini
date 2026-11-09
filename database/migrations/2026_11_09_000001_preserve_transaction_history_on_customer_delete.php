<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['id_nasabah']);
            $table->dropForeign(['id_user']);
            $table->foreignId('id_nasabah')->nullable()->change();
            $table->foreignId('id_user')->nullable()->change();
            $table->foreign('id_nasabah')->references('id')->on('nasabah')->nullOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('koreksi_transaksi', function (Blueprint $table) {
            $table->dropForeign(['id_nasabah']);
            $table->dropForeign(['id_user']);
            $table->foreignId('id_nasabah')->nullable()->change();
            $table->foreignId('id_user')->nullable()->change();
            $table->foreign('id_nasabah')->references('id')->on('nasabah')->nullOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Migration ini bersifat irreversible karena riwayat nasabah mungkin sudah terlepas.');
    }
};
