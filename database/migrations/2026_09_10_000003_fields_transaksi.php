<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sub_teller')->nullable()->after('id_user');
            $table->boolean('konfirmasi_h1')->default(false)->after('nominal');
            $table->string('catatan', 255)->nullable()->after('konfirmasi_h1');

            $table->foreign('id_sub_teller')
                ->references('id')
                ->on('sub_teller')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['id_sub_teller']);
            $table->dropColumn(['id_sub_teller', 'konfirmasi_h1', 'catatan']);
        });
    }
};
