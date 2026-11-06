<?php

use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\PendaftaranNasabah;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);

    $this->account = User::where('username', '20241001')->firstOrFail();
    $this->nasabah = Nasabah::where('user_id', $this->account->id)->firstOrFail();
    $this->teller = User::where('username', 'teller1')->firstOrFail();
    Sanctum::actingAs($this->account);
});

function buatOtorisasi(Nasabah $nasabah, User $teller, array $atribut = []): Transaksi
{
    return Transaksi::create(array_merge([
        'id_nasabah' => $nasabah->id,
        'id_user' => $teller->id,
        'tanggal' => today(),
        'jenis_trans' => Transaksi::JENIS_SETORAN,
        'nominal' => 50000,
        'no_struk' => 'TRX-OT-'.str()->random(8),
        'status' => Transaksi::STATUS_PENDING,
        'kadaluarsa_pada' => now()->addMinutes(5),
        'token_otorisasi' => str()->random(40),
    ], $atribut));
}

it('menampilkan daftar otorisasi milik nasabah yang login', function () {
    $milikSendiri = buatOtorisasi($this->nasabah, $this->teller);
    $nasabahLain = Nasabah::where('user_id', '!=', $this->account->id)->firstOrFail();
    buatOtorisasi($nasabahLain, $this->teller);

    $this->getJson('/api/nasabah/otorisasi?status=menunggu')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $milikSendiri->id)
        ->assertJsonPath('data.0.status', 'menunggu');
});

it('menolak melihat detail transaksi nasabah lain', function () {
    $nasabahLain = Nasabah::where('user_id', '!=', $this->account->id)->firstOrFail();
    $trx = buatOtorisasi($nasabahLain, $this->teller);

    $this->getJson('/api/nasabah/otorisasi/'.$trx->id)->assertNotFound();
});

it('menyetujui transaksi hanya sekali dan memperbarui saldo serta jurnal', function () {
    $trx = buatOtorisasi($this->nasabah, $this->teller);
    $saldoAwal = (int) $this->nasabah->saldo;

    $this->postJson('/api/nasabah/otorisasi/'.$trx->id.'/setuju', ['pin' => '123456'])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'approved');

    expect($trx->fresh()->status)->toBe(Transaksi::STATUS_APPROVED)
        ->and((int) $this->nasabah->fresh()->saldo)->toBe($saldoAwal + 50000)
        ->and(DB::table('jurnal_akuntansi')->where('id_transaksi', $trx->id)->count())->toBe(2);

    $this->postJson('/api/nasabah/otorisasi/'.$trx->id.'/setuju', ['pin' => '123456'])
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    expect((int) $this->nasabah->fresh()->saldo)->toBe($saldoAwal + 50000);
});

it('menolak transaksi tanpa mengubah saldo', function () {
    $trx = buatOtorisasi($this->nasabah, $this->teller);
    $saldoAwal = (int) $this->nasabah->saldo;

    $this->postJson('/api/nasabah/otorisasi/'.$trx->id.'/tolak')
        ->assertOk()
        ->assertJsonPath('data.status', 'ditolak');

    expect($trx->fresh()->status)->toBe(Transaksi::STATUS_DITOLAK)
        ->and((int) $this->nasabah->fresh()->saldo)->toBe($saldoAwal);
});

it('menandai transaksi kedaluwarsa setelah lima menit', function () {
    $trx = buatOtorisasi($this->nasabah, $this->teller, [
        'kadaluarsa_pada' => now()->subSecond(),
    ]);

    $this->getJson('/api/nasabah/otorisasi?status=menunggu')
        ->assertOk()
        ->assertJsonCount(0, 'data');

    expect($trx->fresh()->status)->toBe(Transaksi::STATUS_KEDALUWARSA);
});

it('membatalkan transaksi dan mengunci akun setelah tiga PIN salah', function () {
    $trx = buatOtorisasi($this->nasabah, $this->teller);

    $this->postJson('/api/nasabah/otorisasi/'.$trx->id.'/setuju', ['pin' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('data.attempts_left', 2);
    $this->postJson('/api/nasabah/otorisasi/'.$trx->id.'/setuju', ['pin' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('data.attempts_left', 1);
    $this->postJson('/api/nasabah/otorisasi/'.$trx->id.'/setuju', ['pin' => '000000'])
        ->assertStatus(423)
        ->assertJsonPath('data.attempts_left', 0);

    $this->account->refresh();

    expect($trx->fresh()->status)->toBe(Transaksi::STATUS_DITOLAK)
        ->and($this->account->pin_auth_lock_level)->toBe(1)
        ->and($this->account->pin_auth_locked_until)->not->toBeNull()
        ->and($this->account->pin_auth_admin_lock)->toBeFalse();
});

it('membuka kunci verifikasi PIN setelah PIN diubah petugas', function () {
    $this->account->forceFill([
        'pin_auth_fail_count' => 2,
        'pin_auth_lock_level' => 3,
        'pin_auth_locked_until' => now()->addHour(),
        'pin_auth_admin_lock' => true,
    ])->save();

    PendaftaranNasabah::perbarui($this->nasabah, $this->teller->id, [
        'nama_siswa' => $this->nasabah->nama_siswa,
        'nis' => $this->nasabah->nis,
        'kelas' => $this->nasabah->kelas,
        'email' => $this->account->email,
        'pin_keamanan' => '246810',
    ]);

    $this->account->refresh();

    expect($this->account->pin_auth_fail_count)->toBe(0)
        ->and($this->account->pin_auth_lock_level)->toBe(0)
        ->and($this->account->pin_auth_locked_until)->toBeNull()
        ->and($this->account->pin_auth_admin_lock)->toBeFalse();
});

it('mendaftarkan token perangkat dan mengambil alih token dari akun lain', function () {
    $lain = User::where('username', '20241002')->firstOrFail();
    $lain->forceFill(['fcm_token' => 'token-bersama'])->save();

    $this->postJson('/api/nasabah/perangkat', ['token' => 'token-bersama'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($this->account->fresh()->fcm_token)->toBe('token-bersama')
        ->and($lain->fresh()->fcm_token)->toBeNull();

    $this->postJson('/api/nasabah/perangkat', ['token' => 'token-bersama', 'aktif' => false])
        ->assertOk();

    expect($this->account->fresh()->fcm_token)->toBeNull();
});
