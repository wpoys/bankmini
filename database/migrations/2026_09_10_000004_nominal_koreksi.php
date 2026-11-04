<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('koreksi_transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('nominal')->nullable()->after('id_user');
        });
    }

    public function down(): void
    {
        Schema::table('koreksi_transaksi', function (Blueprint $table) {
            $table->dropColumn('nominal');
        });
    }
};
