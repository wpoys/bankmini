<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NISN terpisah dari NIS dan username login.
        if (! Schema::hasColumn('nasabah', 'nisn')) {
            Schema::table('nasabah', function (Blueprint $table) {
                $table->string('nisn', 20)->nullable()->after('nis');
                $table->index('nisn');
            });
        }

        // Index untuk filter mutasi & perhitungan ringkasan portal nasabah.
        Schema::table('transaksi', function (Blueprint $table) {
            $table->index(['id_nasabah', 'status', 'tanggal'], 'transaksi_nasabah_status_tanggal_index');
            $table->index(['id_nasabah', 'jenis_trans'], 'transaksi_nasabah_jenis_index');
        });

        // Kolom alur tiket pengajuan PIN portal nasabah.
        Schema::table('permintaan_reset_pin', function (Blueprint $table) {
            if (! Schema::hasColumn('permintaan_reset_pin', 'nomor_tiket')) {
                $table->string('nomor_tiket', 40)->nullable()->unique();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'teller_id')) {
                $table->foreignId('teller_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'diajukan_pada')) {
                $table->timestamp('diajukan_pada')->nullable();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'catatan')) {
                $table->string('catatan', 500)->nullable();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'dibatalkan_pada')) {
                $table->timestamp('dibatalkan_pada')->nullable();
            }
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE permintaan_reset_pin MODIFY status ENUM('pending','diproses','ditolak','dibatalkan') NOT NULL DEFAULT 'pending'");
        } else {
            $this->rebuildStatusColumnSqlite();
        }
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex('transaksi_nasabah_status_tanggal_index');
            $table->dropIndex('transaksi_nasabah_jenis_index');
        });

        Schema::table('permintaan_reset_pin', function (Blueprint $table) {
            foreach (['nomor_tiket', 'teller_id', 'supervisor_id', 'diajukan_pada', 'catatan', 'dibatalkan_pada'] as $column) {
                if (Schema::hasColumn('permintaan_reset_pin', $column)) {
                    if (in_array($column, ['teller_id', 'supervisor_id'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        if (Schema::hasColumn('nasabah', 'nisn')) {
            Schema::table('nasabah', function (Blueprint $table) {
                $table->dropIndex(['nisn']);
                $table->dropColumn('nisn');
            });
        }
    }

    /**
     * SQLite menyimpan enum sebagai CHECK constraint. Agar status "ditolak"
     * dan "dibatalkan" dapat dipakai (sekaligus memperbaiki constraint lama
     * yang hanya mengizinkan pending/diproses), tabel dibangun ulang.
     */
    protected function rebuildStatusColumnSqlite(): void
    {
        $rows = DB::table('permintaan_reset_pin')->get();

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('permintaan_reset_pin_tmp');

        Schema::create('permintaan_reset_pin_tmp', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nasabah')->constrained('nasabah')->cascadeOnDelete();
            $table->string('keterangan', 255)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('pin_baru_hash', 255)->nullable();
            $table->string('alasan_penolakan', 500)->nullable();
            $table->string('diajukan_oleh_role', 20)->default('nasabah');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamp('disetujui_at')->nullable();
            $table->timestamp('ditolak_at')->nullable();
            $table->string('nomor_tiket', 40)->nullable()->unique();
            $table->foreignId('teller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diajukan_pada')->nullable();
            $table->string('catatan', 500)->nullable();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        foreach ($rows as $row) {
            DB::table('permintaan_reset_pin_tmp')->insert((array) $row);
        }

        Schema::drop('permintaan_reset_pin');
        Schema::rename('permintaan_reset_pin_tmp', 'permintaan_reset_pin');
        Schema::enableForeignKeyConstraints();
    }
};
