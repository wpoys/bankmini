<?php

use App\Models\JurnalAkuntansi;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('allows admin to access admin dashboard', function () {
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk();
});

it('blocks teller from admin dashboard', function () {
    $teller = User::where('username', 'teller1')->first();

    $this->actingAs($teller)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

it('blocks nasabah from teller dashboard', function () {
    $nasabah = User::where('username', '20241001')->first();

    $this->actingAs($nasabah)
        ->get('/teller/dashboard')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');
});

it('blocks guest from protected dashboards', function () {
    $this->get('/admin/dashboard')->assertRedirect(route('login'));
    $this->get('/teller/dashboard')->assertRedirect(route('login'));
    $this->get('/supervisor/dashboard')->assertRedirect(route('login'));
});

it('allows teller to process setoran transaction', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();
    $saldoAwal = $nasabah->saldo;

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'pin_keamanan' => '123456',
        ])->assertRedirect();

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe($saldoAwal + 50000);

    expect(Transaksi::count())->toBe(1);
    expect(JurnalAkuntansi::count())->toBe(2);
});

it('rejects penarikan above saldo', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241002')->first();
    $saldoAwal = $nasabah->saldo;

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'penarikan',
            'nominal' => 99999999,
            'pin_keamanan' => '654321',
        ])->assertSessionHas('error');

    $nasabah->refresh();
    expect($nasabah->saldo)->toBe($saldoAwal);
});

it('rejects transaction with wrong PIN', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $this->actingAs($teller)
        ->post('/teller/transaksi/proses', [
            'id_nasabah' => $nasabah->id,
            'jenis_trans' => 'setoran',
            'nominal' => 50000,
            'pin_keamanan' => '000000',
        ])->assertSessionHas('error');

    expect(Transaksi::count())->toBe(0);
});

it('allows supervisor to approve pending laporan', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();

    $laporan = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 100000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 100000,
        'status' => 'menunggu_approval',
        'submitted_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/approve", [
            'catatan_supervisor' => 'Laporan valid',
        ])->assertRedirect(route('supervisor.verifikasi.index'));

    $laporan->refresh();
    expect($laporan->status)->toBe('disetujui');
    expect($laporan->approved_by)->toBe($supervisor->id);
});

it('shows kartu simpanan shortcut and full verification link on supervisor dashboard', function () {
    $supervisor = User::where('username', 'supervisor')->first();

    $this->actingAs($supervisor)
        ->get('/supervisor/dashboard')
        ->assertOk()
        ->assertSee('Lihat Semua Verifikasi')
        ->assertSee('Kartu Simpanan')
        ->assertSee(route('supervisor.laporan.kartu-simpanan'), false)
        ->assertDontSee('Ekspor Laporan');

    $this->actingAs($supervisor)
        ->get(route('supervisor.laporan.kartu-simpanan'))
        ->assertOk()
        ->assertSee('Daftar Nasabah')
        ->assertDontSee('Jenis Laporan')
        ->assertDontSee('Rentang Tanggal')
        ->assertDontSee('Excel')
        ->assertDontSee('PDF');
});
