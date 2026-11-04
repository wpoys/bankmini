<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tidak ada perubahan struktur utama yang wajib dilakukan karena:
     * - users.email sudah tersedia (nullable + unique).
     * - nasabah.user_id sudah memiliki foreign key ke users (cascade).
     * - nasabah.no_rekening sudah unik & NOT NULL.
     *
     * Migrasi ini hanya menandai kolom legacy `nasabah.no_hp` sebagai
     * DEPRECATED (tidak dipakai lagi). Kontak nasabah hanya via users.email.
     * Kolom lama TIDAK dihapus agar data lama tidak hilang, tetapi tidak
     * digunakan oleh kode maupun form.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `nasabah` MODIFY `no_hp` VARCHAR(20) NULL '
            ."COMMENT 'DEPRECATED - tidak dipakai; kontak nasabah hanya via email (users.email)'"
        );
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `nasabah` MODIFY `no_hp` VARCHAR(20) NULL');
    }
};
