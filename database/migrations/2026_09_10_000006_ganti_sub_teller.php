<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['id_sub_teller']);
            $table->dropColumn('id_sub_teller');
            $table->string('sub_teller', 20)->nullable()->after('id_user');
        });

        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->dropForeign(['id_sub_teller']);
            $table->dropColumn('id_sub_teller');
            $table->string('sub_teller', 20)->nullable()->after('nomor_teller');
        });

        Schema::dropIfExists('sub_teller');
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn('sub_teller');
            $table->unsignedBigInteger('id_sub_teller')->nullable()->after('id_user');
            $table->foreign('id_sub_teller')->references('id')->on('sub_teller')->nullOnDelete();
        });

        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->dropColumn('sub_teller');
            $table->unsignedBigInteger('id_sub_teller')->nullable()->after('nomor_teller');
            $table->foreign('id_sub_teller')->references('id')->on('sub_teller')->nullOnDelete();
        });

        Schema::create('sub_teller', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teller_id');
            $table->tinyInteger('nomor_sub');
            $table->string('nama_siswa', 100);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['teller_id', 'nomor_sub']);

            $table->foreign('teller_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
