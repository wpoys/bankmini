<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->unsignedTinyInteger('nomor_teller')->nullable()->default(null)->after('teller_id')
                ->comment('Nomor loket teller (1/2/3) yang mengisi laporan');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->dropColumn('nomor_teller');
        });
    }
};
