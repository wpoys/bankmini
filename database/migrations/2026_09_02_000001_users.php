<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique();
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->string('nama_petugas', 100);
            $table->enum('role', ['admin', 'teller', 'supervisor', 'nasabah']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('fcm_token')->nullable()->comment('Token notifikasi untuk aplikasi mobile');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
