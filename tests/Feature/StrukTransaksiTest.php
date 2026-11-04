<?php

namespace Tests\Feature;

use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrukTransaksiTest extends TestCase
{
    use RefreshDatabase;

    protected User $tellerA;
    protected User $tellerB;
    protected User $supervisor;
    protected Nasabah $nasabah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UserSeeder::class);

        $this->tellerA = User::where('username', 'teller1')->firstOrFail();
        $this->tellerB = User::create([
            'username' => 'teller2st',
            'email' => 'teller2st@bankmini.test',
            'password' => 'Teller@2026',
            'nama_petugas' => 'Teller 2 Struk',
            'role' => User::ROLE_TELLER,
            'status' => 'active',
        ]);
        $this->supervisor = User::where('role', 'supervisor')->firstOrFail();

        $this->nasabah = Nasabah::where('nis', '20241001')->firstOrFail();
    }

    public function test_teller_bisa_melihat_struk_transaksi_sendiri(): void
    {
        $transaksi = Transaksi::create([
            'id_nasabah' => $this->nasabah->id,
            'id_user' => $this->tellerA->id,
            'tanggal' => today(),
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'no_struk' => 'TRX-ST-001',
            'status' => 'approved',
        ]);

        $this->actingAs($this->tellerA)
            ->get(route('teller.struk', $transaksi->id))
            ->assertOk()
            ->assertSeeText($transaksi->no_struk)
            ->assertSeeText('Lihat Struk')
            ->assertSeeText('Cetak Struk')
            ->assertSeeText('Download PDF');

        $this->actingAs($this->tellerA)
            ->get(route('teller.struk.cetak', $transaksi->id))
            ->assertOk()
            ->assertSeeText($transaksi->no_struk);

        $this->actingAs($this->tellerA)
            ->get(route('teller.struk.pdf', $transaksi->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_teller_tidak_bisa_membuka_struk_teller_lain(): void
    {
        $transaksi = Transaksi::create([
            'id_nasabah' => $this->nasabah->id,
            'id_user' => $this->tellerB->id,
            'tanggal' => today(),
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'no_struk' => 'TRX-ST-002',
            'status' => 'approved',
        ]);

        $this->actingAs($this->tellerA)
            ->get(route('teller.struk', $transaksi->id))
            ->assertForbidden();

        $this->actingAs($this->tellerA)
            ->get(route('teller.struk.pdf', $transaksi->id))
            ->assertForbidden();
    }

    public function test_supervisor_bisa_melihat_struk_semua_transaksi(): void
    {
        $transaksi = Transaksi::create([
            'id_nasabah' => $this->nasabah->id,
            'id_user' => $this->tellerA->id,
            'tanggal' => today(),
            'jenis_trans' => 'penarikan',
            'nominal' => 20000,
            'no_struk' => 'TRX-ST-003',
            'status' => 'approved',
        ]);

        $this->actingAs($this->supervisor)
            ->get(route('supervisor.struk', $transaksi->id))
            ->assertOk()
            ->assertSeeText($transaksi->no_struk);

        $this->actingAs($this->supervisor)
            ->get(route('supervisor.struk.pdf', $transaksi->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_struk_mengandung_saldo_sebelum_dan_sesudah(): void
    {
        $nasabah = $this->nasabah;
        $nasabah->update(['saldo' => 100000]);

        $transaksi = Transaksi::create([
            'id_nasabah' => $nasabah->id,
            'id_user' => $this->tellerA->id,
            'tanggal' => today(),
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'no_struk' => 'TRX-ST-006',
            'status' => 'approved',
        ]);

        $this->actingAs($this->tellerA)
            ->get(route('teller.struk', $transaksi->id))
            ->assertOk()
            ->assertSeeText(number_format($transaksi->saldo_sebelum, 0, ',', '.'))
            ->assertSeeText(number_format($transaksi->saldo_sesudah, 0, ',', '.'));
    }
}
