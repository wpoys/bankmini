<?php

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

it('memblokir teller melihat laporan teller lain', function () {
    $tellerA = User::where('username', 'teller1')->first();
    $tellerB = User::create([
        'username' => 'teller2ot',
        'email' => 'teller2ot@bankmini.test',
        'password' => 'Teller@2026',
        'nama_petugas' => 'Teller Uji',
        'role' => User::ROLE_TELLER,
        'status' => 'active',
    ]);

    $laporan = LaporanHarian::create([
        'teller_id' => $tellerB->id,
        'tanggal' => today(),
        'saldo_awal_kas' => 10000,
        'total_setoran' => 50000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 60000,
        'status' => 'draft',
    ]);

    $this->actingAs($tellerA)
        ->get("/teller/laporan/{$laporan->id}")
        ->assertForbidden();

    $this->actingAs($tellerB)
        ->get("/teller/laporan/{$laporan->id}")
        ->assertOk();
});

it('memblokir teller melihat struk teller lain', function () {
    $tellerA = User::where('username', 'teller1')->first();
    $tellerB = User::create([
        'username' => 'teller2ot2',
        'email' => 'teller2ot2@bankmini.test',
        'password' => 'Teller@2026',
        'nama_petugas' => 'Teller Uji 2',
        'role' => User::ROLE_TELLER,
        'status' => 'active',
    ]);
    $nasabah = Nasabah::where('nis', '20241001')->first();

    $transaksi = Transaksi::create([
        'id_nasabah' => $nasabah->id,
        'id_user' => $tellerB->id,
        'tanggal' => today(),
        'jenis_trans' => 'setoran',
        'nominal' => 50000,
        'no_struk' => 'TRX-TEST-001',
        'status' => 'approved',
    ]);

    $this->actingAs($tellerA)
        ->get("/teller/transaksi/struk/{$transaksi->id}")
        ->assertForbidden();

    $this->actingAs($tellerB)
        ->get("/teller/transaksi/struk/{$transaksi->id}")
        ->assertOk();
});

it('halaman login hanya menampilkan kredensial petugas', function () {
    $response = $this->get('/login')->assertOk();

    $html = $response->getContent();
    expect($html)->toContain('Admin@2026');
    expect($html)->toContain('Teller@2026');
    expect($html)->toContain('Super@2026');
    expect($html)->not->toContain('Nasabah@2026');
    expect($html)->not->toContain('admin123');
});
