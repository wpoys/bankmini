<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koreksi transaksi diperluas:
 * - transaksi_id menjadi nullable → mendukung koreksi TANPA transaksi asli
 *   (penemuan uang rusak/palsu/selisih yang tidak ditautkan ke struk).
 * - kolom jenis (koreksi_setoran/koreksi_penarikan) sebagai arah koreksi
 *   yang eksplisit dipilih teller.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('koreksi_transaksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaksi_id');
            $table->unsignedBigInteger('transaksi_id')->nullable()->after('id');
            $table->string('jenis', 32)->nullable()->after('transaksi_id');

            $table->foreign('transaksi_id')
                ->references('id')
                ->on('transaksi')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('koreksi_transaksi', function (Blueprint $table) {
            $table->dropForeign(['transaksi_id']);
            $table->dropColumn(['transaksi_id', 'jenis']);
            $table->foreignId('transaksi_id')->constrained('transaksi')->restrictOnDelete();
        });
    }
};