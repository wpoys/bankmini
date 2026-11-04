<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Terapkan kembali batasan CHECK yang tertulis di migrasi awal tetapi
     * tidak terwujud sebagai constraint di server MySQL saat ini.
     *
     * Aturan bisnis yang diformalkan:
     * - transaksi.nominal        >  0  (nominal transaksi tidak boleh nol/negatif)
     * - nasabah.saldo            >= 0  (saldo nasabah tidak boleh negatif)
     * - jurnal_akuntansi.jumlah  >  0  (posisi debit/kredit selalu bernilai positif)
     * - rekonsiliasi_kas.total_fisik >= 0
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $checks = [
            ['transaksi', 'transaksi_nominal_positif', 'nominal > 0'],
            ['nasabah', 'nasabah_saldo_non_negatif', 'saldo >= 0'],
            ['jurnal_akuntansi', 'jurnal_akuntansi_jumlah_positif', 'jumlah > 0'],
            ['rekonsiliasi_kas', 'rekonsiliasi_kas_total_fisik_non_negatif', 'total_fisik >= 0'],
        ];

        foreach ($checks as [$table, $name, $expr]) {
            $existing = DB::selectOne(
                'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                   AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "CHECK"',
                [$table, $name]
            );

            if ($existing) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expr})");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $checks = [
            ['transaksi', 'transaksi_nominal_positif'],
            ['nasabah', 'nasabah_saldo_non_negatif'],
            ['jurnal_akuntansi', 'jurnal_akuntansi_jumlah_positif'],
            ['rekonsiliasi_kas', 'rekonsiliasi_kas_total_fisik_non_negatif'],
        ];

        foreach ($checks as [$table, $name]) {
            DB::statement("ALTER TABLE `{$table}` DROP CHECK `{$name}`");
        }
    }
};
