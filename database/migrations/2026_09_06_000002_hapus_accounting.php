<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userIds = DB::table('users')->where('role', 'accounting')->pluck('id');

        DB::table('activity_logs')->whereIn('id_user', $userIds)->delete();

        DB::table('users')->where('role', 'accounting')->delete();
    }

    public function down(): void
    {
        //
    }
};
