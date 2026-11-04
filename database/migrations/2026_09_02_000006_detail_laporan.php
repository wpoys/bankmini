<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_harian')->cascadeOnDelete();
            $table->foreignId('transaksi_id')->constrained('transaksi')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['laporan_id', 'transaksi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_laporan');
    }
};
