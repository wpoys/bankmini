<?php

use App\Models\JurnalAkuntansi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\KodeAkun;
use App\Support\PendaftaranNasabah;
use Database\Seeders\UserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    $this->admin = User::where('username', 'admin')->first();
});

function dataPendaftaran(array $overrides = []): array
{
    return array_merge([
        'nama_siswa' => 'Ahmad Fauzi',
        'nis' => '20260901',
        'kelas' => 'RPL 1',
        'produk' => 'siswa',
        'email' => 'ahmad.fauzi@test.test',
        'pin_keamanan' => '123456',
        'pin_keamanan_confirmation' => '123456',
        'saldo_awal' => 0,
    ], $overrides);
}

it('menyimpan email nasabah ke users.email saat membuat nasabah', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran())
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();
    $akun = $nasabah->user;

    expect($akun)->not->toBeNull();
    expect($akun->email)->toBe('ahmad.fauzi@test.test');
});

it('nasabah tanpa email tetap bisa dibuat (users.email null)', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['email' => '']))
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();
    expect($nasabah->user->email)->toBeNull();
});

it('membuat akun user otomatis dengan username=NIS dan role nasabah', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran())
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();
    $akun = $nasabah->user;

    expect($akun->username)->toBe('20260901');
    expect($akun->role)->toBe('nasabah');
    expect($akun->status)->toBe('active');
});

it('membuat nomor rekening otomatis sesuai jurusan kelas dan QR mengikuti rekening', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['kelas' => 'DKV 1']))
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();

    expect($nasabah->no_rekening)->toMatch('/^DKV-\d{3}$/');
    expect($nasabah->kode_jurusan)->toBe('DKV');
    expect($nasabah->qr_code)->toBe($nasabah->no_rekening);
});

it('menyimpan PIN ter-hash pada nasabah dan sebagai password user', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['pin_keamanan' => '789012', 'pin_keamanan_confirmation' => '789012']))
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();

    expect($nasabah->pin_keamanan)->not->toBe('789012');
    expect(Hash::check('789012', $nasabah->pin_keamanan))->toBeTrue();
    expect(Hash::check('789012', $nasabah->user->password))->toBeTrue();
});

it('menolak NIS yang sudah terdaftar saat mendaftar nasabah baru', function () {
    $this->actingAs($this->admin)->post('/admin/nasabah', dataPendaftaran())->assertRedirect();

    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['nama_siswa' => 'Duplikat NIS']))
        ->assertSessionHasErrors('nis');
});

it('menolak email yang sudah dipakai nasabah lain', function () {
    $this->actingAs($this->admin)->post('/admin/nasabah', dataPendaftaran())->assertRedirect();

    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['nis' => '20260902', 'nama_siswa' => 'Email Sama']))
        ->assertSessionHasErrors('email');
});

it('menolak PIN yang bukan 6 digit', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['pin_keamanan' => '123', 'pin_keamanan_confirmation' => '123']))
        ->assertSessionHasErrors('pin_keamanan');
});

it('menolak PIN yang tidak cocok dengan konfirmasinya', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['pin_keamanan_confirmation' => '654321']))
        ->assertSessionHasErrors('pin_keamanan');
});

it('menolak saldo awal negatif', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['saldo_awal' => -5000]))
        ->assertSessionHasErrors('saldo_awal');
});

it('menolak produk yang tidak terdaftar', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['produk' => 'deposito']))
        ->assertSessionHasErrors('produk');
});

it('rollback total ketika penyimpanan gagal di tengah proses', function () {
    Nasabah::create([
        'user_id' => null,
        'no_rekening' => 'ZZZ999',
        'kode_jurusan' => 'RPL',
        'qr_code' => 'ZZZ999',
        'nis' => '20260901',
        'nama_siswa' => 'Nasabah Lama Tanpa Akun',
        'kelas' => 'RPL 1',
        'produk' => 'siswa',
        'pin_keamanan' => Hash::make('123456'),
        'saldo' => 0,
    ]);

    expect(fn () => PendaftaranNasabah::buat($this->admin->id, [
        'nama_siswa' => 'Ahmad Fauzi',
        'nis' => '20260901',
        'kelas' => 'RPL 1',
        'produk' => 'siswa',
        'pin_keamanan' => '123456',
    ]))->toThrow(QueryException::class);

    expect(User::where('username', '20260901')->exists())->toBeFalse();
});

it('setoran awal dicatat sebagai transaksi setoran + jurnal dan menambah saldo', function () {
    $sebelum = Transaksi::count();

    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['saldo_awal' => 100000]))
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();
    expect((int) $nasabah->saldo)->toBe(100000);

    $transaksi = Transaksi::where('id_nasabah', $nasabah->id)->first();
    expect($transaksi)->not->toBeNull();
    expect($transaksi->jenis_trans)->toBe(Transaksi::JENIS_SETORAN);
    expect($transaksi->status)->toBe(Transaksi::STATUS_APPROVED);
    expect((int) $transaksi->nominal)->toBe(100000);
    expect(Transaksi::count())->toBe($sebelum + 1);

    $jurnal = JurnalAkuntansi::where('id_transaksi', $transaksi->id)->get();
    expect($jurnal)->toHaveCount(2);
    expect($jurnal->where('kode_akun', KodeAkun::KAS)->first()->posisi)->toBe('debit');
    expect($jurnal->where('kode_akun', KodeAkun::TABUNGAN)->first()->posisi)->toBe('kredit');
});

it('tidak membuat transaksi saat saldo awal 0', function () {
    $sebelum = Transaksi::count();

    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['saldo_awal' => 0]))
        ->assertRedirect(route('admin.nasabah.index'));

    expect(Transaksi::count())->toBe($sebelum);
});

it('edit nasabah menyinkronkan email dan PIN ke akun login', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran())
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();

    $this->actingAs($this->admin)
        ->put("/admin/nasabah/{$nasabah->id}", [
            'nama_siswa' => 'Ahmad Fauzi Baru',
            'nis' => '20260901',
            'kelas' => 'DKV 2',
            'produk' => 'guru',
            'email' => 'ahmad.baru@test.test',
            'pin_keamanan' => '999888',
            'pin_keamanan_confirmation' => '999888',
        ])->assertRedirect(route('admin.nasabah.index'));

    $nasabah->refresh();

    expect($nasabah->kode_jurusan)->toBe('DKV');
    expect(Hash::check('999888', $nasabah->pin_keamanan))->toBeTrue();
    expect($nasabah->user->email)->toBe('ahmad.baru@test.test');
    expect(Hash::check('999888', $nasabah->user->password))->toBeTrue();
});

it('edit tanpa PIN tidak mengubah password lama', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran(['pin_keamanan' => '123456', 'pin_keamanan_confirmation' => '123456']))
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();
    $passwordLama = $nasabah->user->password;

    $this->actingAs($this->admin)
        ->put("/admin/nasabah/{$nasabah->id}", [
            'nama_siswa' => $nasabah->nama_siswa,
            'nis' => $nasabah->nis,
            'kelas' => $nasabah->kelas,
            'produk' => $nasabah->produk,
            'email' => 'ahmad.fauzi@test.test',
            'pin_keamanan' => '',
            'pin_keamanan_confirmation' => '',
        ])->assertRedirect(route('admin.nasabah.index'));

    $nasabah->refresh();
    expect($nasabah->user->password)->toBe($passwordLama);
    expect(Hash::check('123456', $nasabah->pin_keamanan))->toBeTrue();
});

it('edit tetap berhasil untuk nasabah legacy tanpa email', function () {
    $nasabah = PendaftaranNasabah::buat($this->admin->id, [
        'nama_siswa' => 'Legacy Tanpa Email',
        'nis' => '20260955',
        'kelas' => 'RPL 1',
        'produk' => 'siswa',
        'pin_keamanan' => '123456',
    ]);

    expect($nasabah->user->email)->toBeNull();

    $this->actingAs($this->admin)
        ->put("/admin/nasabah/{$nasabah->id}", [
            'nama_siswa' => 'Legacy Diubah',
            'nis' => $nasabah->nis,
            'kelas' => $nasabah->kelas,
            'produk' => $nasabah->produk,
            'email' => '',
            'pin_keamanan' => '',
            'pin_keamanan_confirmation' => '',
        ])->assertRedirect(route('admin.nasabah.index'));

    $nasabah->refresh();
    expect($nasabah->nama_siswa)->toBe('Legacy Diubah');
    expect($nasabah->user->email)->toBeNull();
});

it('admin dapat membuat nasabah lewat route admin', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran())
        ->assertRedirect(route('admin.nasabah.index'));

    expect(Nasabah::where('nis', '20260901')->exists())->toBeTrue();
});

it('import csv berhasil membuat nasabah beserta akun login', function () {
    $csv = "nis,nama,kelas,produk,email,pin,saldo_awal\n"
        ."20270001,Ahmad Fauzi,RPL 1,siswa,fuazi@test.test,111111,50000\n"
        ."20270002,Budi Hartono,DKV 2,guru,bud@test.test,222222,0\n";

    $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
    file_put_contents($tmp, $csv);
    $file = new UploadedFile($tmp, 'import-nasabah.csv', 'text/csv', null, true);

    $this->actingAs($this->admin)
        ->post('/admin/nasabah/import', ['file' => $file])
        ->assertOk()
        ->assertSee('Ahmad Fauzi');

    $this->actingAs($this->admin)
        ->post('/admin/nasabah/import/konfirmasi')
        ->assertRedirect(route('admin.nasabah.import.hasil'));

    $nasabah = Nasabah::where('nis', '20270001')->firstOrFail();
    expect($nasabah->user->username)->toBe('20270001');
    expect($nasabah->user->role)->toBe('nasabah');
    expect(Hash::check('111111', $nasabah->user->password))->toBeTrue();
    expect((int) $nasabah->saldo)->toBe(50000);

    unlink($tmp);
});

it('import menolak file yang memuat kolom no_hp', function () {
    $csv = "nis,nama,kelas,produk,no_hp,pin\n"
        ."20270001,Ahmad Fauzi,RPL 1,siswa,081234567890,111111\n";

    $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
    file_put_contents($tmp, $csv);
    $file = new UploadedFile($tmp, 'import-nasabah.csv', 'text/csv', null, true);

    $this->actingAs($this->admin)
        ->post('/admin/nasabah/import', ['file' => $file])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Nasabah::where('nis', '20270001')->exists())->toBeFalse();

    unlink($tmp);
});

it('import melaporkan error per baris dan hanya menyimpan baris valid', function () {
    $csv = "nis,nama,kelas,produk,email,pin,saldo_awal\n"
        ."20270001,Ahmad Fauzi,RPL 1,siswa,valid@test.test,111111,50000\n"
        ."20270002,PIN Salah,RPL 2,siswa,invalid@test.test,12,0\n";

    $tmp = tempnam(sys_get_temp_dir(), 'csv').'.csv';
    file_put_contents($tmp, $csv);
    $file = new UploadedFile($tmp, 'import-nasabah.csv', 'text/csv', null, true);

    $this->actingAs($this->admin)
        ->post('/admin/nasabah/import', ['file' => $file])
        ->assertOk()
        ->assertSee('PIN Salah');

    $this->actingAs($this->admin)
        ->post('/admin/nasabah/import/konfirmasi')
        ->assertRedirect(route('admin.nasabah.import.hasil'));

    expect(Nasabah::where('nis', '20270001')->exists())->toBeTrue();
    expect(Nasabah::where('nis', '20270002')->exists())->toBeFalse();

    unlink($tmp);
});

it('nasabah baru tidak menyimpan no_hp apa pun ke database', function () {
    $this->actingAs($this->admin)
        ->post('/admin/nasabah', dataPendaftaran())
        ->assertRedirect(route('admin.nasabah.index'));

    $nasabah = Nasabah::where('nis', '20260901')->firstOrFail();

    expect($nasabah->isFillable('no_hp'))->toBeFalse();
    expect($nasabah->no_hp)->toBeNull();
});
