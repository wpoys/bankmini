<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('nasabah', 'nisn')) {
            return;
        }

        Schema::table('nasabah', function (Blueprint $table) {
            $table->dropIndex(['nisn']);
            $table->dropColumn('nisn');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('nasabah', 'nisn')) {
            return;
        }

        Schema::table('nasabah', function (Blueprint $table) {
            $table->string('nisn', 20)->nullable()->after('nis');
            $table->index('nisn');
        });
    }
};
