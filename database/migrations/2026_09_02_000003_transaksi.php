<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nasabah')->constrained('nasabah')->restrictOnDelete();
            $table->foreignId('id_user')->constrained('users')->restrictOnDelete();
            $table->date('tanggal')->nullable();
            $table->enum('jenis_trans', ['setoran', 'penarikan'])->nullable();
            $table->decimal('nominal', 15, 2)->check('nominal > 0');
            $table->string('no_struk', 50)->nullable()->unique();
            $table->enum('status', ['pending', 'approved'])->default('approved');
            $table->boolean('dikoreksi')->default(false)->comment('Ditandai saat transaksi dikoreksi (data asli tidak diubah)');
            $table->timestamps();

            $table->index(['id_nasabah', 'tanggal']);
            $table->index(['id_user', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
