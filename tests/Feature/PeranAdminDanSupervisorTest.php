<?php

use App\Models\JurnalAkuntansi;
use App\Models\KoreksiTransaksi;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\Transaksi;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('allows admin to access admin dashboard and manage nasabah', function () {
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertOk();

    $this->actingAs($admin)
        ->get('/admin/nasabah')
        ->assertOk();
});

it('blocks teller from admin dashboard', function () {
    $teller = User::where('username', 'teller1')->first();

    $this->actingAs($teller)
        ->get('/admin/dashboard')
        ->assertForbidden();
});

it('blocks nasabah from admin nasabah index', function () {
    $nasabah = User::where('username', '20241001')->first();

    $this->actingAs($nasabah)
        ->get('/admin/nasabah')
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');
});

it('allows admin to download nasabah import template', function () {
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($admin)
        ->get('/admin/nasabah/import/template')
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=template-import-nasabah.csv');
});

it('allows supervisor to access all report pages', function () {
    $supervisor = User::where('username', 'supervisor')->first();

    $routes = [
        '/supervisor/laporan/dashboard',
        '/supervisor/laporan/laporan-harian',
        '/supervisor/laporan/buku-besar',
        '/supervisor/laporan/jurnal',
        '/supervisor/laporan/mutasi-nasabah',
        '/supervisor/laporan/saldo-produk',
        '/supervisor/laporan/laporan-tahunan',
        '/supervisor/koreksi',
    ];

    foreach ($routes as $rute) {
        $this->actingAs($supervisor)->get($rute)->assertOk();
    }
});

it('blocks teller from supervisor reports', function () {
    $teller = User::where('username', 'teller1')->first();

    $this->actingAs($teller)
        ->get('/supervisor/laporan/jurnal')
        ->assertForbidden();
});

it('blocks supervisor from teller transactions and admin users', function () {
    $supervisor = User::where('username', 'supervisor')->first();

    $this->actingAs($supervisor)
        ->get('/teller/transaksi')
        ->assertForbidden();

    $this->actingAs($supervisor)
        ->get('/admin/users')
        ->assertForbidden();
});

it('allows supervisor to approve koreksi transaksi and update saldo + jurnal', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $transaksiAsli = Transaksi::create([
        'id_nasabah' => $nasabah->id,
        'id_user' => $teller->id,
        'tanggal' => today(),
        'no_struk' => 'STR-'.uniqid(),
        'jenis_trans' => 'setoran',
        'nominal' => 75000,
        'status' => 'approved',
    ]);
    $nasabah->increment('saldo', 75000);

    $koreksi = KoreksiTransaksi::create([
        'transaksi_id' => $transaksiAsli->id,
        'id_nasabah' => $nasabah->id,
        'id_user' => $teller->id,
        'alasan' => 'Setoran ganda',
        'status' => 'menunggu_approval',
    ]);

    $saldoAwal = (int) $nasabah->fresh()->saldo;

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/approve")
        ->assertRedirect(route('supervisor.koreksi.index'))
        ->assertSessionHas('success');

    $nasabah->refresh();

    expect((int) $nasabah->saldo)->toBe($saldoAwal - 75000);

    $pembalik = Transaksi::where('koreksi_id', $koreksi->id)->first();
    expect($pembalik)->not->toBeNull();
    expect($pembalik->jenis_trans)->toBe('penarikan');
    expect(JurnalAkuntansi::where('id_transaksi', $pembalik->id)->count())->toBe(2);
});

it('allows supervisor to reject koreksi transaksi without touching saldo', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241002')->first();

    $transaksiAsli = Transaksi::create([
        'id_nasabah' => $nasabah->id,
        'id_user' => $teller->id,
        'tanggal' => today(),
        'no_struk' => 'STR-'.uniqid(),
        'jenis_trans' => 'penarikan',
        'nominal' => 50000,
        'status' => 'approved',
    ]);

    $koreksi = KoreksiTransaksi::create([
        'transaksi_id' => $transaksiAsli->id,
        'id_nasabah' => $nasabah->id,
        'id_user' => $teller->id,
        'alasan' => 'Nominal salah',
        'status' => 'menunggu_approval',
    ]);

    $saldoAwal = (int) $nasabah->fresh()->saldo;

    $this->actingAs($supervisor)
        ->post("/supervisor/koreksi/{$koreksi->id}/tolak", ['catatan_approver' => 'Nominal transaksi sudah sesuai'])
        ->assertRedirect(route('supervisor.koreksi.index'))
        ->assertSessionHas('success');

    $nasabah->refresh();

    expect((int) $nasabah->saldo)->toBe($saldoAwal);
    expect(JurnalAkuntansi::where('id_transaksi', $koreksi->id)->count())->toBe(0);
});

it('allows supervisor to download pdf report', function () {
    $supervisor = User::where('username', 'supervisor')->first();
    $teller = User::where('username', 'teller1')->first();

    $query = http_build_query([
        'format' => 'pdf',
        'mulai' => now()->startOfMonth()->format('Y-m-d'),
        'sampai' => now()->format('Y-m-d'),
        'teller' => $teller->id,
    ]);

    $pdf = $this->actingAs($supervisor)->get('/supervisor/laporan/jurnal?'.$query);
    $pdf->assertOk();
    expect($pdf->headers->get('content-type'))->toContain('application/pdf');
});

it('allows admin to process reset PIN request', function () {
    $admin = User::where('username', 'admin')->first();
    $nasabah = Nasabah::where('nis', '20241003')->first();

    $permintaan = PermintaanResetPin::create([
        'id_nasabah' => $nasabah->id,
        'keterangan' => 'Nasabah lupa PIN.',
        'pin_baru_hash' => Hash::make('999888'),
        'status' => PermintaanResetPin::STATUS_PENDING,
        'diajukan_oleh_role' => 'nasabah',
    ]);

    $this->actingAs($admin)
        ->post("/admin/nasabah/permintaan-reset/{$permintaan->id}/proses")
        ->assertRedirect()
        ->assertSessionHas('success');

    $permintaan->refresh();
    $nasabah->refresh();

    expect($permintaan->status)->toBe(PermintaanResetPin::STATUS_DIPROSES);
    expect($permintaan->diproses_oleh)->toBe($admin->id);
    expect(Hash::check('999888', $nasabah->pin_keamanan))->toBeTrue();
});
