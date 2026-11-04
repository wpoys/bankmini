<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_petugas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Petugas user (admin/supervisor/teller/accounting/cs)');
            $table->unsignedBigInteger('sub_teller_id')->nullable()->comment('Petugas Sub Teller (terikat akun Teller)');
            $table->date('tanggal');
            $table->string('hari', 20)->nullable()->comment('Otomatis dari tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('status', 20)->default('dijadwalkan')->comment('dijadwalkan/bertugas/selesai/dibatalkan');
            $table->text('keterangan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
            $table->index(['sub_teller_id', 'tanggal']);
            $table->index(['tanggal', 'status']);

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sub_teller_id')->references('id')->on('sub_tellers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_petugas');
    }
};