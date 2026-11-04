<?php

use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
});

function apiNasabah(string $nis = '20241001'): Nasabah
{
    return Nasabah::with('user')->where('nis', $nis)->firstOrFail();
}

function apiToken(Nasabah $nasabah, string $name = 'test'): string
{
    return $nasabah->user->createToken($name)->plainTextToken;
}

/**
 * Klien pengujian memakai satu instance aplikasi untuk semua request, sehingga
 * guard sanctum menyimpan user yang pertama kali ter-resolve. Reset guard dulu
 * agar pergantian user di tengah test benar-benar berganti.
 */
function apiAs(Nasabah $nasabah, string $name = 'test')
{
    $test = test();
    app('auth')->forgetGuards();

    return $test->withToken(apiToken($nasabah, $name));
}

function apiTransaksi(Nasabah $nasabah, array $extra = []): Transaksi
{
    return Transaksi::create(array_merge([
        'id_nasabah' => $nasabah->id,
        'id_user' => User::where('username', 'teller1')->value('id'),
        'tanggal' => now()->subDays(3)->toDateString(),
        'jenis_trans' => Transaksi::JENIS_SETORAN,
        'nominal' => 50000,
        'no_struk' => 'STRUK-'.Transaksi::count().'-'.random_int(1000, 9999),
        'status' => Transaksi::STATUS_APPROVED,
    ], $extra));
}

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/

it('login nasabah mengembalikan token dan data profil', function () {
    $this->postJson('/api/login', [
        'username' => '20241001',
        'password' => 'Nasabah@2026',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.username', '20241001')
        ->assertJsonPath('user.role', 'nasabah')
        ->assertJsonPath('user.nasabah.nis', '20241001')
        ->assertJsonPath('user.nasabah.produk_label', 'Tabungan Siswa')
        ->assertJsonStructure(['token', 'user']);
});

it('login nasabah menerima PIN keamanan 6 digit (aplikasi mobile)', function () {
    $this->postJson('/api/login', [
        'username' => '20241001',
        'password' => '123456', // PIN keamanan dari UserSeeder
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.username', '20241001')
        ->assertJsonPath('user.role', 'nasabah')
        ->assertJsonStructure(['token', 'user']);
});

it('login menolak password/PIN yang salah', function () {
    $this->postJson('/api/login', [
        'username' => '20241001',
        'password' => '999999',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Username atau password salah.');
});

it('login menolak role selain nasabah', function () {
    $this->postJson('/api/login', [
        'username' => 'teller1',
        'password' => 'Teller@2026',
    ])->assertStatus(422)
        ->assertJsonFragment(['message' => 'Aplikasi ini khusus untuk nasabah.']);
});

it('menolak akses endpoint terlindungi tanpa token', function () {
    $this->getJson('/api/nasabah/dashboard')->assertUnauthorized();
});

it('menolak akses ketika akun dinonaktifkan', function () {
    $nasabah = apiNasabah();
    $nasabah->user->update(['status' => 'inactive']);

    apiAs($nasabah)
        ->getJson('/api/nasabah/dashboard')
        ->assertStatus(403)
        ->assertJsonPath('message', 'Akun Anda dinonaktifkan. Hubungi Administrator.');
});

/*
|--------------------------------------------------------------------------
| Dashboard, saldo, profil
|--------------------------------------------------------------------------
*/

it('dashboard mengembalikan ringkasan saldo dan mutasi terbaru', function () {
    $nasabah = apiNasabah();
    $nasabah->update(['saldo' => 150000]);

    apiTransaksi($nasabah, [
        'nominal' => 100000,
        'jenis_trans' => Transaksi::JENIS_SETORAN,
        'tanggal' => now()->subDays(5)->toDateString(),
    ]);
    apiTransaksi($nasabah, [
        'nominal' => 20000,
        'jenis_trans' => Transaksi::JENIS_PENARIKAN,
        'tanggal' => now()->subDays(3)->toDateString(),
    ]);
    apiTransaksi($nasabah, [
        'nominal' => 30000,
        'jenis_trans' => Transaksi::JENIS_SETORAN,
        'tanggal' => now()->subDay()->toDateString(),
        'status' => Transaksi::STATUS_PENDING,
    ]);

    $response = apiAs($nasabah)
        ->getJson('/api/nasabah/dashboard')
        ->assertOk()
        ->assertJsonPath('data.nasabah.nis', '20241001')
        ->assertJsonPath('data.nasabah.saldo', 150000)
        ->assertJsonPath('data.nasabah.saldo_rupiah', 'Rp 150.000')
        // Total mengikuti jenis transaksi (parity dengan dashboard web),
        // jadi transaksi pending ikut terhitung dan ditandai lewat field terpisah.
        ->assertJsonPath('data.ringkasan.total_transaksi', 3)
        ->assertJsonPath('data.ringkasan.total_setoran', 130000)
        ->assertJsonPath('data.ringkasan.total_setoran_rupiah', 'Rp 130.000')
        ->assertJsonPath('data.ringkasan.total_penarikan', 20000)
        ->assertJsonPath('data.ringkasan.transaksi_pending', 1)
        ->assertJsonPath('data.notifikasi_belum_dibaca', 0)
        ->assertJsonCount(3, 'data.transaksi_terbaru');

    // Transaksi terbaru diurutkan dari tanggal paling baru.
    expect($response->json('data.transaksi_terbaru.0.nominal'))->toBe(30000)
        ->and($response->json('data.transaksi_terbaru.0.arah'))->toBe('masuk')
        ->and($response->json('data.transaksi_terbaru.1.arah'))->toBe('keluar');
});

it('profil mengembalikan data rekening user dan isi QR', function () {
    $nasabah = apiNasabah();

    apiAs($nasabah)
        ->getJson('/api/nasabah/profil')
        ->assertOk()
        ->assertJsonPath('data.nis', '20241001')
        ->assertJsonPath('data.nama_siswa', 'Budi Santoso')
        ->assertJsonPath('data.status', 'aktif')
        ->assertJsonPath('data.user.username', '20241001')
        ->assertJsonPath('data.user.role_label', 'Nasabah')
        ->assertJsonPath('data.qr_code', $nasabah->no_rekening);
});

it('saldo mengembalikan data nasabah', function () {
    $nasabah = apiNasabah();
    $nasabah->update(['saldo' => 75000]);

    apiAs($nasabah)
        ->getJson('/api/nasabah/saldo')
        ->assertOk()
        ->assertJsonPath('data.saldo', 75000)
        ->assertJsonPath('data.saldo_rupiah', 'Rp 75.000');
});

it('endpoint nasabah menolak 404 bila user tidak punya data nasabah', function () {
    $admin = User::where('role', 'admin')->firstOrFail();
    $test = test();
    app('auth')->forgetGuards();

    $test->withToken($admin->createToken('test')->plainTextToken)
        ->getJson('/api/nasabah/dashboard')
        ->assertStatus(404)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Data nasabah tidak ditemukan.');
});

/*
|--------------------------------------------------------------------------
| Mutasi
|--------------------------------------------------------------------------
*/

it('transaksi difilter berdasarkan jenis dan rentang tanggal', function () {
    $nasabah = apiNasabah();

    apiTransaksi($nasabah, ['jenis_trans' => Transaksi::JENIS_SETORAN, 'tanggal' => now()->subDays(10)->toDateString()]);
    apiTransaksi($nasabah, ['jenis_trans' => Transaksi::JENIS_PENARIKAN, 'tanggal' => now()->subDays(2)->toDateString()]);
    apiTransaksi($nasabah, ['jenis_trans' => Transaksi::JENIS_SETORAN, 'tanggal' => now()->subDay()->toDateString()]);

    apiAs($nasabah)->getJson('/api/nasabah/transaksi?jenis=setoran')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);

    apiAs($nasabah, 'kedua')->getJson('/api/nasabah/transaksi?jenis=penarikan')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.arah', 'keluar');

    apiAs($nasabah, 'ketiga')->getJson('/api/nasabah/transaksi?dari='.now()->subDays(5)->toDateString())
        ->assertOk()
        ->assertJsonCount(2, 'data');

    apiAs($nasabah, 'keempat')->getJson('/api/nasabah/transaksi?sampai='.now()->subDays(5)->toDateString())
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('transaksi bisa dicari lewat nomor struk', function () {
    $nasabah = apiNasabah();

    apiTransaksi($nasabah, ['no_struk' => 'STRK-AAA-111']);
    apiTransaksi($nasabah, ['no_struk' => 'STRK-BBB-222']);

    apiAs($nasabah)
        ->getJson('/api/nasabah/transaksi?q=AAA')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.no_struk', 'STRK-AAA-111');
});

it('transaksi memvalidasi rentang tanggal yang terbalik', function () {
    apiAs(apiNasabah())
        ->getJson('/api/nasabah/transaksi?dari=2026-02-01&sampai=2026-01-01')
        ->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Tanggal akhir tidak boleh lebih dulu dari tanggal awal.',
        ]);
});

it('transaksi memvalidasi jenis yang tidak dikenal', function () {
    apiAs(apiNasabah())
        ->getJson('/api/nasabah/transaksi?jenis=transfer')
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Jenis transaksi tidak dikenal.']);
});

it('transaksi dipaginasi dengan metadata', function () {
    $nasabah = apiNasabah();

    foreach (range(1, 5) as $i) {
        apiTransaksi($nasabah, ['tanggal' => now()->subDays($i)->toDateString()]);
    }

    apiAs($nasabah)
        ->getJson('/api/nasabah/transaksi?per_page=2&page=2')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('meta.last_page', 3)
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', 5);
});

it('detail transaksi hanya bisa diakses pemiliknya', function () {
    $nasabah = apiNasabah('20241001');
    $pemilik = apiNasabah('20241002');
    $transaksi = apiTransaksi($pemilik);

    apiAs($nasabah)
        ->getJson("/api/nasabah/transaksi/{$transaksi->id}")
        ->assertNotFound()
        ->assertJsonPath('message', 'Transaksi tidak ditemukan.');

    apiAs($pemilik)
        ->getJson("/api/nasabah/transaksi/{$transaksi->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $transaksi->id)
        ->assertJsonPath('data.no_rekening', $pemilik->no_rekening)
        ->assertJsonPath('data.nama_siswa', $pemilik->nama_siswa)
        ->assertJsonPath('data.nis', $pemilik->nis)
        ->assertJsonPath('data.kelas', $pemilik->kelas)
        ->assertJsonPath('data.produk_label', $pemilik->produk_label)
        ->assertJsonPath('data.sekolah.nama', 'BANK MINI SEKOLAH')
        ->assertJsonPath('data.arah', 'masuk')
        ->assertJsonStructure(['data' => [
            'teller',
            'catatan',
            'konfirmasi_h1',
            'dikoreksi',
            'saldo_sebelum',
            'saldo_sesudah',
            'qr_content',
            'sekolah' => ['nama', 'jenis', 'tagline', 'alamat', 'lembaga', 'keterangan'],
        ]]);
});

it('cetak mutasi mengembalikan pdf dan json tanpa data internal', function () {
    $nasabah = apiNasabah();
    apiTransaksi($nasabah);

    apiAs($nasabah)
        ->get('/api/nasabah/transaksi/cetak?format=pdf')
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    apiAs($nasabah, 'json')
        ->getJson('/api/nasabah/transaksi/cetak?format=json')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.mutasi.0.no', 1)
        ->assertJsonMissingPath('data.mutasi.0.transaksi');
});

/*
|--------------------------------------------------------------------------
| Ubah PIN
|--------------------------------------------------------------------------
*/

it('ubah pin membuat pengajuan tanpa langsung mengubah pin', function () {
    $nasabah = apiNasabah();

    apiAs($nasabah)
        ->postJson('/api/nasabah/ubah-pin', [
            'pin_lama' => '123456',
            'pin_baru' => '246810',
            'pin_baru_confirmation' => '246810',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Pengajuan perubahan PIN terkirim. Menunggu persetujuan petugas. PIN lama Anda tetap berlaku.');

    $nasabah->refresh();

    expect(Hash::check('123456', $nasabah->pin_keamanan))->toBeTrue()
        ->and(Hash::check('Nasabah@2026', $nasabah->user->fresh()->password))->toBeTrue();

    $this->assertDatabaseHas('permintaan_reset_pin', [
        'id_nasabah' => $nasabah->id,
        'status' => PermintaanResetPin::STATUS_PENDING,
    ]);
});

it('ubah pin menolak pin lama yang salah', function () {
    apiAs(apiNasabah())
        ->postJson('/api/nasabah/ubah-pin', [
            'pin_lama' => '000000',
            'pin_baru' => '246810',
            'pin_baru_confirmation' => '246810',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'PIN lama tidak sesuai.');
});

it('ubah pin menolak pin baru yang sama dengan pin lama', function () {
    apiAs(apiNasabah())
        ->postJson('/api/nasabah/ubah-pin', [
            'pin_lama' => '123456',
            'pin_baru' => '123456',
            'pin_baru_confirmation' => '123456',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'PIN baru harus berbeda dari PIN lama.');
});

it('ubah pin tidak mengubah pin maupun token sebelum disetujui', function () {
    $nasabah = apiNasabah();
    $tokenLama = apiToken($nasabah, 'lama');
    $tokenAktif = apiToken($nasabah, 'aktif');

    $test = test();
    app('auth')->forgetGuards();

    $test->withToken($tokenAktif)
        ->postJson('/api/nasabah/ubah-pin', [
            'pin_lama' => '123456',
            'pin_baru' => '246810',
            'pin_baru_confirmation' => '246810',
        ])
        ->assertOk();

    $this->assertDatabaseHas('personal_access_tokens', [
        'token' => hash('sha256', explode('|', $tokenLama, 2)[1]),
    ]);

    app('auth')->forgetGuards();
    $test->withToken($tokenAktif)->getJson('/api/me')->assertOk();

    expect(Hash::check('123456', $nasabah->fresh()->pin_keamanan))->toBeTrue();
});

it('menampilkan status pengajuan pin terakhir milik nasabah', function () {
    $nasabah = apiNasabah();

    PermintaanResetPin::create([
        'id_nasabah' => $nasabah->id,
        'keterangan' => 'Pengajuan perubahan PIN oleh nasabah.',
        'pin_baru_hash' => Hash::make('246810'),
        'status' => PermintaanResetPin::STATUS_PENDING,
        'diajukan_oleh_role' => 'nasabah',
        'diajukan_pada' => now(),
    ]);

    apiAs($nasabah)
        ->getJson('/api/nasabah/pengajuan-pin')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.keterangan', 'Pengajuan perubahan PIN oleh nasabah.');
});

it('menampilkan alasan penolakan pengajuan pin', function () {
    $nasabah = apiNasabah();

    PermintaanResetPin::create([
        'id_nasabah' => $nasabah->id,
        'keterangan' => 'Pengajuan perubahan PIN oleh nasabah.',
        'pin_baru_hash' => null,
        'status' => PermintaanResetPin::STATUS_DITOLAK,
        'diajukan_oleh_role' => 'nasabah',
        'diajukan_pada' => now(),
        'alasan_penolakan' => 'Harus konfirmasi loket',
    ]);

    apiAs($nasabah)
        ->getJson('/api/nasabah/pengajuan-pin')
        ->assertOk()
        ->assertJsonPath('data.status', 'ditolak')
        ->assertJsonPath('data.alasan_penolakan', 'Harus konfirmasi loket');
});

/*
|--------------------------------------------------------------------------
| Notifikasi
|--------------------------------------------------------------------------
*/

it('daftar notifikasi menampilkan jumlah belum dibaca', function () {
    $nasabah = apiNasabah();

    UserNotification::kirim($nasabah->user, UserNotification::TIPE_SUCCESS, 'Setoran Berhasil', 'Setoran Rp 50.000.');
    UserNotification::kirim($nasabah->user, UserNotification::TIPE_WARNING, 'PIN Diubah', 'Pinsetelan keamanan.');

    apiAs($nasabah)
        ->getJson('/api/notifikasi')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.belum_dibaca', 2)
        ->assertJsonPath('data.0.sudah_dibaca', false)
        ->assertJsonPath('data.0.tipe', 'warning');

    apiAs($nasabah, 'kedua')
        ->getJson('/api/notifikasi/belum-dibaca')
        ->assertOk()
        ->assertJsonPath('data.belum_dibaca', 2);
});

it('notifikasi bisa ditandai dibaca dan dihapus', function () {
    $nasabah = apiNasabah();
    $notifikasi = UserNotification::kirim($nasabah->user, UserNotification::TIPE_INFO, 'Info', 'Pesan');

    apiAs($nasabah)
        ->postJson("/api/notifikasi/{$notifikasi->id}/baca")
        ->assertOk()
        ->assertJsonPath('data.sudah_dibaca', true);

    apiAs($nasabah, 'kedua')
        ->deleteJson("/api/notifikasi/{$notifikasi->id}")
        ->assertOk()
        ->assertJsonPath('data.belum_dibaca', 0);

    $this->assertDatabaseMissing('user_notifications', ['id' => $notifikasi->id]);
});

it('tandai semua notifikasi dibaca', function () {
    $nasabah = apiNasabah();

    UserNotification::kirim($nasabah->user, UserNotification::TIPE_INFO, 'Satu', 'Pesan satu');
    UserNotification::kirim($nasabah->user, UserNotification::TIPE_INFO, 'Dua', 'Pesan dua');

    apiAs($nasabah)
        ->postJson('/api/notifikasi/baca-semua')
        ->assertOk()
        ->assertJsonPath('data.belum_dibaca', 0);

    expect($nasabah->user->userNotifications()->belumDibaca()->count())->toBe(0);
});

it('menolak notifikasi milik pengguna lain', function () {
    $pemilik = apiNasabah('20241001');
    $orangLain = apiNasabah('20241002');
    $notifikasi = UserNotification::kirim($pemilik->user, UserNotification::TIPE_INFO, 'Rahasia', 'Pesan');

    apiAs($orangLain)
        ->postJson("/api/notifikasi/{$notifikasi->id}/baca")
        ->assertForbidden()
        ->assertJsonPath('message', 'Anda tidak memiliki akses ke notifikasi ini.');

    apiAs($orangLain, 'kedua')
        ->deleteJson("/api/notifikasi/{$notifikasi->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('user_notifications', ['id' => $notifikasi->id]);
});
