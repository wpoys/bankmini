<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifikasi transaksi supervisor berdasarkan tanggal (per hari).
        Schema::create('verifikasi_harian', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique()->comment('Satu baris verifikasi per tanggal');
            $table->unsignedInteger('jumlah_transaksi')->default(0);
            $table->unsignedBigInteger('total_setoran')->default(0);
            $table->unsignedBigInteger('total_penarikan')->default(0);
            $table->unsignedBigInteger('total_kas')->comment('Setoran dikurangi penarikan');
            $table->bigInteger('selisih')->nullable()->comment('Nilai dari rekonsiliasi teller, null bila tidak ada laporan');
            $table->unsignedInteger('transaksi_tercatat')->default(0)->comment('Jumlah transaksi saat verifikasi, untuk deteksi transaksi baru');
            $table->foreignId('verifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verifikasi_at')->nullable();
            $table->timestamps();

            $table->index('verifikasi_at');
        });

        // Pengajuan perubahan PIN oleh nasabah: simpan hash PIN baru.
        // PIN baru TIDAK PERNAH disimpan sebagai plaintext.
        Schema::table('permintaan_reset_pin', function (Blueprint $table) {
            if (! Schema::hasColumn('permintaan_reset_pin', 'pin_baru_hash')) {
                $table->string('pin_baru_hash', 255)->nullable()->comment('Hash PIN baru, tidak pernah plaintext');
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'alasan_penolakan')) {
                $table->string('alasan_penolakan', 500)->nullable();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'diajukan_oleh_role')) {
                $table->string('diajukan_oleh_role', 20)->default('nasabah')->comment('nasabah/petugas');
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'disetujui_at')) {
                $table->timestamp('disetujui_at')->nullable();
            }
            if (! Schema::hasColumn('permintaan_reset_pin', 'ditolak_at')) {
                $table->timestamp('ditolak_at')->nullable();
            }
        });

        // Perlebaran status agar bisa menyimpan "ditolak" (rejected).
        // SQLite tidak mendukung MODIFY ... ENUM, sehingga lewati di sana.
        if (Schema::hasTable('permintaan_reset_pin')
            && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE permintaan_reset_pin MODIFY status ENUM('pending','diproses','ditolak') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('permintaan_reset_pin', function (Blueprint $table) {
            $table->dropColumn([
                'pin_baru_hash',
                'alasan_penolakan',
                'diajukan_oleh_role',
                'disetujui_at',
                'ditolak_at',
            ]);
        });

        Schema::dropIfExists('verifikasi_harian');
    }
};
