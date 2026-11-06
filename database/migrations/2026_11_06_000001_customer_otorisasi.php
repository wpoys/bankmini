<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->timestamp('kadaluarsa_pada')->nullable();
            $table->unsignedTinyInteger('otorisasi_percobaan')->default(0);
            $table->string('token_otorisasi', 64)->nullable()->unique();
        });

        $statuses = "'pending','approved','ditolak','kedaluwarsa'";
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `transaksi` MODIFY `status` ENUM({$statuses}) NOT NULL DEFAULT 'approved'");
        } else {
            Schema::table('transaksi', function (Blueprint $table) {
                $table->enum('status', ['pending', 'approved', 'ditolak', 'kedaluwarsa'])->default('approved')->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('pin_auth_fail_count')->default(0);
            $table->unsignedTinyInteger('pin_auth_lock_level')->default(0);
            $table->timestamp('pin_auth_locked_until')->nullable();
            $table->boolean('pin_auth_admin_lock')->default(false);
        });

    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropUnique(['token_otorisasi']);
            $table->dropColumn(['kadaluarsa_pada', 'otorisasi_percobaan', 'token_otorisasi']);
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `transaksi` MODIFY `status` ENUM('pending','approved','ditolak') NOT NULL DEFAULT 'approved'");
            } else {
                $table->enum('status', ['pending', 'approved', 'ditolak'])->default('approved')->change();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin_auth_fail_count', 'pin_auth_lock_level', 'pin_auth_locked_until', 'pin_auth_admin_lock']);
        });
    }
};
