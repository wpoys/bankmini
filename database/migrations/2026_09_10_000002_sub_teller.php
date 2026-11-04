<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_teller', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teller_id');
            $table->tinyInteger('nomor_sub');
            $table->string('nama_siswa', 100);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['teller_id', 'nomor_sub']);

            $table->foreign('teller_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_teller');
    }
};
