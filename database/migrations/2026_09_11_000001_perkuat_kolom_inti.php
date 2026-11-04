<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perkuat kolom inti agar sesuai ERD:
     *
     * 1. nasabah.no_rekening  -> NOT NULL (nomor rekening wajib & unik).
     * 2. transaksi.tanggal     -> NOT NULL (tanggal transaksi wajib).
     *
     * Data lama yang masih NULL di-backfill lebih dulu secara defensif
     * agar migrasi aman dijalankan pada database yang sudah berisi data.
     */
    public function up(): void
    {
        DB::table('nasabah')->whereNull('no_rekening')->orderBy('id')->each(function ($row) {
            DB::table('nasabah')->where('id', $row->id)->update([
                'no_rekening' => 'TMP'.(string) $row->nis,
            ]);
        });

        DB::table('transaksi')->whereNull('tanggal')->orderBy('id')->each(function ($row) {
            $createdAt = (string) ($row->created_at ?? '');
            $tanggal = strlen($createdAt) >= 10 ? substr($createdAt, 0, 10) : '2026-01-01';

            DB::table('transaksi')->where('id', $row->id)->update(['tanggal' => $tanggal]);
        });

        Schema::table('nasabah', function (Blueprint $table) {
            $table->string('no_rekening', 30)->nullable(false)->change();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->date('tanggal')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->string('no_rekening', 30)->nullable()->change();
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->date('tanggal')->nullable()->change();
        });
    }
};
