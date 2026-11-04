<?php

use App\Models\Nasabah;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

function nasabahTanpaUser(): Nasabah
{
    return Nasabah::create([
        'user_id' => null,
        'no_rekening' => 'TEST001',
        'kode_jurusan' => 'RPL',
        'qr_code' => 'TEST001',
        'nis' => '20000001',
        'nama_siswa' => 'Tes Tanpa Akun',
        'kelas' => 'RPL 1',
        'produk' => 'siswa',
        'pin_keamanan' => Hash::make('123456'),
        'saldo' => 0,
    ]);
}

it('membuat akun user untuk nasabah yang belum memiliki user lewat command', function () {
    $nasabah = nasabahTanpaUser();

    expect(Nasabah::whereNull('user_id')->count())->toBe(1);

    $this->artisan('nasabah:backfill-user')
        ->expectsOutputToContain('Selesai')
        ->assertSuccessful();

    $nasabah->refresh();

    expect($nasabah->user)->not->toBeNull();
    expect($nasabah->user->username)->toBe($nasabah->nis);
    expect($nasabah->user->role)->toBe('nasabah');
    expect($nasabah->user->status)->toBe('active');
    expect(Hash::check(User::DEFAULT_PASSWORD, $nasabah->user->password))->toBeTrue();
});

it('ensureUser idempoten untuk nasabah yang sudah memiliki user', function () {
    $nasabah = Nasabah::where('nis', '20241001')->firstOrFail();
    $userLama = $nasabah->user;

    $user = $nasabah->ensureUser();

    expect($user->id)->toBe($userLama->id);
    expect(Nasabah::whereNull('user_id')->count())->toBe(0);
});
