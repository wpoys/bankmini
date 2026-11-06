<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('verifikasi_harian', function (Blueprint $table) {
            $table->string('status', 20)->default('diterima')->after('selisih');
            $table->text('catatan')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('verifikasi_harian', function (Blueprint $table) {
            $table->dropColumn(['status', 'catatan']);
        });
    }
};
