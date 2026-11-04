<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomor_sequence', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->integer('tahun');
            $table->bigInteger('nilai')->default(0);
            $table->timestamps();

            $table->unique(['nama', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nomor_sequence');
    }
};
