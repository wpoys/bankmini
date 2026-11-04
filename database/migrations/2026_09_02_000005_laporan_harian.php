<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teller_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal');
            $table->decimal('saldo_awal_kas', 15, 2)->default(0);
            $table->decimal('total_setoran', 15, 2)->default(0);
            $table->decimal('total_penarikan', 15, 2)->default(0);
            $table->decimal('saldo_akhir_sistem', 15, 2)->default(0);
            $table->decimal('saldo_akhir_fisik', 15, 2)->nullable();
            $table->decimal('selisih', 15, 2)->nullable();
            $table->enum('status', ['draft', 'menunggu_approval', 'disetujui', 'ditolak'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_supervisor')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('kas_dipindahkan', 15, 2)->nullable()->comment('Nominal kas yang dipindahkan ke brankas saat laporan disetujui');
            $table->foreignId('kas_dipindahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kas_dipindahkan_at')->nullable();
            $table->timestamps();

            $table->unique(['teller_id', 'tanggal']);
            $table->index(['status', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_harian');
    }
};
