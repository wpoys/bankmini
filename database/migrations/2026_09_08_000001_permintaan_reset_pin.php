<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perluas peran: canonical 'customer_service' (menggantikan 'cs'),
     * tambah 'accounting', nilai jenis transaksi koreksi, dan tabel permintaan
     * reset PIN untuk modul Customer Service.
     */
    public function up(): void
    {
        // 1) Migrasi nilai role legacy 'cs' menjadi 'customer_service'.
        DB::table('users')->where('role', 'cs')->update(['role' => 'customer_service']);

        // 2) Perluas enum role (mempertahankan 'cs' agar data lama tetap terbaca).
        $roles = ['admin', 'teller', 'supervisor', 'nasabah', 'cs', 'customer_service', 'accounting'];

        $this->alterEnum('users', 'role', $roles, false);

        // 3) Perluas enum jenis transaksi untuk koreksi berbasis transaksi.
        $jenis = ['setoran', 'penarikan', 'koreksi_setoran', 'koreksi_penarikan'];

        $this->alterEnum('transaksi', 'jenis_trans', $jenis, true);

        // 4) Status transaksi ditambah 'ditolak' untuk koreksi yang ditolak Accounting.
        $this->alterEnum('transaksi', 'status', ['pending', 'approved', 'ditolak'], false, 'approved');

        // 5) Kolom jejak verifikasi koreksi oleh Accounting.
        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });

        // 6) Tabel permintaan reset PIN.
        Schema::create('permintaan_reset_pin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_nasabah')->constrained('nasabah')->cascadeOnDelete();
            $table->string('keterangan', 255)->nullable();
            $table->enum('status', ['pending', 'diproses'])->default('pending');
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_reset_pin');

        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('approved_at');
        });

        $this->alterEnum('transaksi', 'status', ['pending', 'approved'], false, 'approved');
        $this->alterEnum('transaksi', 'jenis_trans', ['setoran', 'penarikan'], true);
        $this->alterEnum('users', 'role', ['admin', 'teller', 'supervisor', 'nasabah'], false);

        DB::table('users')->where('role', 'customer_service')->update(['role' => 'cs']);
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
