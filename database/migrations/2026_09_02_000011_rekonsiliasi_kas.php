<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_kas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_harian')->cascadeOnDelete();
            $table->foreignId('teller_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_fisik', 15, 2)->default(0)->check('total_fisik >= 0');
            $table->decimal('selisih', 15, 2)->nullable();
            $table->enum('status', ['cocok', 'selisih'])->default('selisih');
            $table->timestamps();

            $table->unique('laporan_id');
        });

        Schema::create('detail_rekonsiliasi_kas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekonsiliasi_id')->constrained('rekonsiliasi_kas')->cascadeOnDelete();
            $table->integer('pecahan')->comment('Nominal pecahan uang (contoh: 100000, 50000)');
            $table->integer('jumlah_lembar');
            $table->bigInteger('subtotal');
            $table->timestamps();

            $table->index('rekonsiliasi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_rekonsiliasi_kas');
        Schema::dropIfExists('rekonsiliasi_kas');
    }
};
