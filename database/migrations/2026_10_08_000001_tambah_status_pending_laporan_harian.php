<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->enum('status', ['draft', 'menunggu_approval', 'pending', 'disetujui', 'ditolak'])
                ->default('draft')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('laporan_harian')->where('status', 'pending')->update(['status' => 'menunggu_approval']);

        Schema::table('laporan_harian', function (Blueprint $table) {
            $table->enum('status', ['draft', 'menunggu_approval', 'disetujui', 'ditolak'])
                ->default('draft')
                ->change();
        });
    }
};
