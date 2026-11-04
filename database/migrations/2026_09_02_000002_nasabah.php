<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nasabah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('no_rekening', 30)->nullable()->unique();
            $table->string('nis', 30)->unique();
            $table->string('nama_siswa', 100);
            $table->string('kelas', 50);
            $table->string('no_hp', 20)->nullable();
            $table->string('pin_keamanan')->nullable();
            $table->decimal('saldo', 15, 2)->default(0)->check('saldo >= 0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nasabah');
    }
};
