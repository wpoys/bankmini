<?php

use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

it('membuat notifikasi melalui helper kirim', function () {
    $admin = User::where('username', 'admin')->firstOrFail();

    $notifikasi = UserNotification::kirim(
        $admin,
        UserNotification::TIPE_INFO,
        'Percobaan',
        'Ini pesan uji.',
        route('admin.dashboard'),
    );

    expect($notifikasi->tipe)->toBe('info');
    expect($notifikasi->user_id)->toBe($admin->id);
    expect($notifikasi->sudah_dibaca)->toBeFalse();
});

it('menampilkan daftar notifikasi milik user', function () {
    $admin = User::where('username', 'admin')->firstOrFail();

    UserNotification::kirim($admin, UserNotification::TIPE_SUCCESS, 'Transaksi Berhasil', 'Setoran Rp 50.000.');

    $this->actingAs($admin)
        ->get(route('notifikasi.index'))
        ->assertOk()
        ->assertSee('Transaksi Berhasil')
        ->assertSee('Setoran Rp 50.000.');
});

it('menandai notifikasi dibaca dan menuju tautan', function () {
    $admin = User::where('username', 'admin')->firstOrFail();

    $notifikasi = UserNotification::kirim(
        $admin,
        UserNotification::TIPE_SUCCESS,
        'Transaksi Berhasil',
        'Setoran Rp 50.000.',
        route('admin.dashboard'),
    );

    $this->actingAs($admin)
        ->post(route('notifikasi.baca', $notifikasi->id))
        ->assertRedirect(route('admin.dashboard'));

    expect($notifikasi->fresh()->sudah_dibaca)->toBeTrue();
});

it('menandai semua notifikasi dibaca', function () {
    $admin = User::where('username', 'admin')->firstOrFail();

    UserNotification::kirim($admin, UserNotification::TIPE_INFO, 'Satu', 'Pesan satu');
    UserNotification::kirim($admin, UserNotification::TIPE_WARNING, 'Dua', 'Pesan dua');

    $this->actingAs($admin)
        ->post(route('notifikasi.baca-semua'))
        ->assertRedirect();

    expect($admin->userNotifications()->belumDibaca()->count())->toBe(0);
});

it('menghapus notifikasi', function () {
    $admin = User::where('username', 'admin')->firstOrFail();
    $notifikasi = UserNotification::kirim($admin, UserNotification::TIPE_INFO, 'Akan dihapus', 'Pesan');

    $this->actingAs($admin)
        ->delete(route('notifikasi.hapus', $notifikasi->id))
        ->assertRedirect();

    expect(UserNotification::find($notifikasi->id))->toBeNull();
});

it('menolak akses notifikasi milik user lain', function () {
    $pemilik = User::where('username', 'admin')->firstOrFail();
    $orangLain = User::where('username', 'teller1')->firstOrFail();

    $notifikasi = UserNotification::kirim($pemilik, UserNotification::TIPE_INFO, 'Rahasia', 'Pesan rahasia');

    $this->actingAs($orangLain)
        ->post(route('notifikasi.baca', $notifikasi->id))
        ->assertForbidden();

    $this->actingAs($orangLain)
        ->delete(route('notifikasi.hapus', $notifikasi->id))
        ->assertForbidden();
});

it('memberi notifikasi sukses transaksi kepada nasabah dan gagal kepada teller', function () {
    $teller = User::where('username', 'teller1')->first();
    $nasabah = Nasabah::where('nis', '20241001')->firstOrFail();

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'setoran',
        'nominal' => 50000,
        'pin_keamanan' => '123456',
    ])->assertRedirect();

    expect($nasabah->user->userNotifications()->exists())->toBeTrue();
    expect($nasabah->user->userNotifications()->where('tipe', 'success')->first()->judul)
        ->toBe('Transaksi Setoran Berhasil');

    $this->actingAs($teller)->post('/teller/transaksi/proses', [
        'id_nasabah' => $nasabah->id,
        'jenis_trans' => 'setoran',
        'nominal' => 5000,
        'pin_keamanan' => '000000',
    ])->assertSessionHas('error');

    expect($teller->userNotifications()->where('tipe', 'warning')->exists())->toBeTrue();
});

it('memberi notifikasi ke supervisor saat laporan disubmit', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();

    $laporan = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 0,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 0,
        'saldo_akhir_fisik' => 0,
        'selisih' => 0,
        'status' => 'draft',
    ]);

    $this->actingAs($teller)
        ->post("/teller/laporan/{$laporan->id}/submit")
        ->assertSessionHas('success');

    expect($supervisor->userNotifications()->where('tipe', 'info')->first()->judul)
        ->toBe('Laporan Menunggu Verifikasi');
});

it('memberi notifikasi ke teller saat laporan disetujui dan ditolak', function () {
    $teller = User::where('username', 'teller1')->first();
    $supervisor = User::where('username', 'supervisor')->first();

    $laporan = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 0,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 0,
        'status' => 'menunggu_approval',
        'submitted_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan->id}/approve", ['catatan_supervisor' => 'OK'])
        ->assertRedirect(route('supervisor.verifikasi.index'));

    expect($teller->userNotifications()->where('tipe', 'success')->first()->judul)
        ->toBe('Laporan Disetujui');

    $laporan2 = LaporanHarian::create([
        'teller_id' => $teller->id,
        'tanggal' => today()->addDay(),
        'saldo_awal_kas' => 0,
        'total_setoran' => 0,
        'total_penarikan' => 0,
        'saldo_akhir_sistem' => 0,
        'status' => 'menunggu_approval',
        'submitted_at' => now(),
    ]);

    $this->actingAs($supervisor)
        ->post("/supervisor/verifikasi/{$laporan2->id}/reject", ['catatan_supervisor' => 'Data kurang'])
        ->assertRedirect(route('supervisor.verifikasi.index'));

    expect($teller->userNotifications()->where('tipe', 'danger')->first()->judul)
        ->toBe('Laporan Ditolak');
});
