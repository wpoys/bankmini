<?php

use App\Models\Brankas;
use App\Models\LaporanHarian;
use App\Models\MutasiBrankas;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('shows shared vault balance on all role dashboards', function () {
    Brankas::query()->findOrFail(1)->update(['saldo' => 250000]);

    $this->actingAs(User::where('username', 'admin')->first())
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Rp 250.000');

    $this->actingAs(User::where('username', 'supervisor')->first())
        ->get(route('supervisor.dashboard'))
        ->assertOk()
        ->assertSee('Rp 250.000');

    $this->actingAs(User::where('username', 'teller1')->first())
        ->get(route('teller.dashboard'))
        ->assertOk()
        ->assertSee('Rp 250.000');
});

it('lets admin set the vault balance and records an adjustment', function () {
    $admin = User::where('username', 'admin')->first();

    $this->actingAs($admin)
        ->put(route('admin.brankas.update'), ['saldo' => 500000])
        ->assertRedirect(route('admin.dashboard'));

    expect(Brankas::saldo())->toBe(500000)
        ->and(MutasiBrankas::where('jenis', 'koreksi_admin')->value('nominal'))->toBe(500000);
});

it('requires teller password and deducts confirmed opening cash from the vault', function () {
    $teller = User::where('username', 'teller1')->first();
    Brankas::query()->findOrFail(1)->update(['saldo' => 300000]);

    $this->actingAs($teller)
        ->post(route('teller.brankas.ambil'), ['jumlah' => 100000, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect(Brankas::saldo())->toBe(300000);

    $this->actingAs($teller)
        ->post(route('teller.brankas.ambil'), ['jumlah' => 100000, 'password' => 'Teller@2026'])
        ->assertRedirect(route('teller.dashboard'));

    expect(Brankas::saldo())->toBe(200000)
        ->and(MutasiBrankas::where('jenis', 'kas_awal_teller')->sum('nominal'))->toBe(-100000);
});

it('allows multiple teller cash requests in one day and includes them in the report opening cash', function () {
    $teller = User::where('username', 'teller1')->first();
    Brankas::query()->findOrFail(1)->update(['saldo' => 300000]);

    foreach ([50000, 70000] as $jumlah) {
        $this->actingAs($teller)
            ->post(route('teller.brankas.ambil'), ['jumlah' => $jumlah, 'password' => 'Teller@2026'])
            ->assertRedirect(route('teller.dashboard'));
    }

    $this->actingAs($teller)
        ->get(route('teller.laporan.create'))
        ->assertOk()
        ->assertSee('Rp 120.000')
        ->assertSee('Total kas awal diambil dari brankas hari ini');
});

it('excludes yesterday cash returned to the vault from next day teller opening cash', function () {
    $teller = User::where('username', 'teller1')->first();
    LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today()->subDay(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 100000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 100000,
        'saldo_akhir_fisik' => 100000,
        'selisih' => 0,
        'status' => 'disetujui',
        'kas_dipindahkan' => 40000,
    ]);

    $this->actingAs($teller)
        ->get(route('teller.laporan.create'))
        ->assertOk()
        ->assertSee('Rp 60.000');
});

it('adds report cash to the vault only when the supervisor approves the report', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();
    $laporan = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 100000,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 100000,
        'saldo_akhir_fisik' => 100000,
        'selisih' => 0,
        'status' => 'menunggu_approval',
        'kas_dipindahkan' => 40000,
    ]);
    Brankas::query()->findOrFail(1)->update(['saldo' => 250000]);

    $this->actingAs($supervisor)
        ->post(route('supervisor.verifikasi.approve', $laporan), ['catatan_supervisor' => 'Sesuai'])
        ->assertRedirect();

    expect(Brankas::saldo())->toBe(290000)
        ->and(MutasiBrankas::where('laporan_id', $laporan->id)->where('jenis', 'setoran_laporan')->value('nominal'))->toBe(40000);
});

it('rejects teller cash request above the available vault balance', function () {
    $teller = User::where('username', 'teller1')->first();
    Brankas::query()->findOrFail(1)->update(['saldo' => 50000]);

    $this->actingAs($teller)
        ->post(route('teller.brankas.ambil'), ['jumlah' => 100000, 'password' => 'Teller@2026'])
        ->assertSessionHasErrors('jumlah');

    expect(Brankas::saldo())->toBe(50000)
        ->and(MutasiBrankas::where('jenis', 'kas_awal_teller')->count())->toBe(0);
});
