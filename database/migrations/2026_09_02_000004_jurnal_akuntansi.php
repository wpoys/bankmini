<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnal_akuntansi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_transaksi')->nullable()->constrained('transaksi')->restrictOnDelete()->comment('Null untuk jurnal non-transaksi (mis. pemindahan kas ke brankas)');
            $table->string('kode_akun', 50);
            $table->enum('posisi', ['debit', 'kredit']);
            $table->decimal('jumlah', 15, 2)->check('jumlah > 0');
            $table->timestamps();

            $table->index('id_transaksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnal_akuntansi');
    }
};
