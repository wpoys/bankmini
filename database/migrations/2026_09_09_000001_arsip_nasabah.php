<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->boolean('arsip')->default(false)->after('saldo')
                ->comment('Nasabah alumni yang diarsipkan; hanya data arsip yang boleh dihapus permanen.');
            $table->index('arsip');
        });
    }

    public function down(): void
    {
        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropIndex(['arsip']);
            $table->dropColumn('arsip');
        });
    }
};
