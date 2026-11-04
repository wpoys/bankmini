<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('koreksi_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->restrictOnDelete();
            $table->foreignId('id_nasabah')->constrained('nasabah')->restrictOnDelete();
            $table->foreignId('id_user')->constrained('users')->restrictOnDelete();
            $table->text('alasan');
            $table->enum('status', ['menunggu_approval', 'disetujui', 'ditolak'])->default('menunggu_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('catatan_approver')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreignId('koreksi_id')->nullable()->after('id_user')->constrained('koreksi_transaksi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('koreksi_id');
        });

        Schema::dropIfExists('koreksi_transaksi');
    }
};
