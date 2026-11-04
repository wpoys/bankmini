<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Konsolidasi peran: Customer Service dilebur ke Admin, Accounting
     * dilebur ke Supervisor. Struktur role aktif menjadi 4: admin,
     * supervisor, teller, nasabah.
     */
    public function up(): void
    {
        // 1) Customer Service ('cs' legacy & 'customer_service') -> Admin.
        DB::table('users')->whereIn('role', ['cs', 'customer_service'])->update(['role' => 'admin']);

        // 2) Accounting -> Supervisor.
        DB::table('users')->where('role', 'accounting')->update(['role' => 'supervisor']);

        // 3) Sempitkan enum role hanya ke 4 role aktif.
        $this->alterEnum('users', 'role', ['admin', 'teller', 'supervisor', 'nasabah'], false);
    }

    public function down(): void
    {
        // Batalkan perubahan enum; nilai role yang sudah dilebur tidak bisa
        // dikembalikan otomatis.
        $this->alterEnum('users', 'role', ['admin', 'teller', 'supervisor', 'nasabah', 'cs', 'customer_service', 'accounting'], false);
    }

    protected function alterEnum(string $table, string $column, array $values, bool $nullable, ?string $default = null): void
    {
        $quoted = implode(',', array_map(fn ($v) => "'".addslashes((string) $v)."'", $values));

        if (DB::getDriverName() === 'mysql') {
            $sql = "ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$quoted}) ".($nullable ? 'NULL' : 'NOT NULL');

            if ($default !== null) {
                $sql .= " DEFAULT '".addslashes($default)."'";
            }

            DB::statement($sql);

            return;
        }

        Schema::table($table, function (Blueprint $table) use ($column, $values, $nullable, $default) {
            $column = $table->enum($column, $values);

            if ($nullable) {
                $column->nullable();
            }

            if ($default !== null) {
                $column->default($default);
            }

            $column->change();
        });
    }
};