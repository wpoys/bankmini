<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nasabah_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('file_name');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->string('status', 20)->default('previewed');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('nasabah_import_batch_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('nasabah_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->string('status', 20);
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nasabah_import_batch_rows');
        Schema::dropIfExists('nasabah_import_batches');
    }
};
