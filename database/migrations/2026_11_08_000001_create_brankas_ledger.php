<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brankas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('saldo')->default(0);
            $table->timestamps();
        });

        Schema::create('mutasi_brankas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brankas_id')->constrained('brankas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('laporan_id')->nullable()->constrained('laporan_harian')->nullOnDelete();
            $table->string('jenis', 40);
            $table->bigInteger('nominal');
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->unique(['laporan_id', 'jenis']);
            $table->index(['user_id', 'created_at']);
        });

        DB::table('brankas')->insert([
            'id' => 1,
            'saldo' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_brankas');
        Schema::dropIfExists('brankas');
    }
};
