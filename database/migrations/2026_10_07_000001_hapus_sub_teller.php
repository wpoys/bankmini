<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('jadwal_petugas', 'sub_teller_id')) {
            Schema::table('jadwal_petugas', function (Blueprint $table) {
                $table->dropForeign(['sub_teller_id']);
                $table->dropIndex(['sub_teller_id', 'tanggal']);
                $table->dropColumn('sub_teller_id');
            });
        }

        if (Schema::hasColumn('transaksi', 'sub_teller')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('sub_teller');
            });
        }

        if (Schema::hasColumn('laporan_harian', 'sub_teller')) {
            Schema::table('laporan_harian', function (Blueprint $table) {
                $table->dropColumn('sub_teller');
            });
        }

        Schema::dropIfExists('sub_tellers');
        Schema::dropIfExists('sub_teller');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('jadwal_petugas', 'sub_teller_id')) {
            Schema::table('jadwal_petugas', function (Blueprint $table) {
                $table->unsignedBigInteger('sub_teller_id')->nullable()->after('user_id')->comment('Petugas Sub Teller (terikat akun Teller)');
                $table->index(['sub_teller_id', 'tanggal']);
                $table->foreign('sub_teller_id')->references('id')->on('sub_tellers')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('transaksi', 'sub_teller')) {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->string('sub_teller', 20)->nullable()->after('id_user');
            });
        }

        if (! Schema::hasColumn('laporan_harian', 'sub_teller')) {
            Schema::table('laporan_harian', function (Blueprint $table) {
                $table->string('sub_teller', 20)->nullable()->after('nomor_teller');
            });
        }

        if (! Schema::hasTable('sub_tellers')) {
            Schema::create('sub_tellers', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 20)->unique();
                $table->string('nama', 100);
                $table->string('keterangan')->nullable();
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }
    }
};
