<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_tellers', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique()->comment('Kode operasional, nilai back-compatible: subteller_1..n');
            $table->string('nama', 100)->comment('Nama petugas Sub Teller');
            $table->string('keterangan', 255)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index('aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_tellers');
    }
};