<?php

use App\Mail\OtpMail;
use App\Models\Nasabah;
use App\Models\User;
use App\Support\ResetPasswordOtp;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserSeeder::class);
    Mail::fake();
});

function lupaNasabah(): Nasabah
{
    return Nasabah::with('user')->where('nis', '20241001')->firstOrFail();
}

function lupaKirimOtp(array $extra = [])
{
    $nasabah = lupaNasabah();

    return test()->postJson('/api/lupa-password/kirim-otp', array_merge([
        'nis' => '20241001',
        'no_rekening' => $nasabah->no_rekening,
        'pin_keamanan' => '123456',
    ], $extra));
}

function lupaKodeOtp(): string
{
    $kode = null;

    Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$kode) {
        $kode = $mail->code;

        return true;
    });

    return (string) $kode;
}

/*
|--------------------------------------------------------------------------
| Kirim OTP
|--------------------------------------------------------------------------
*/

it('mengirim otp dan mengembalikan email tujuan', function () {
    $nasabah = lupaNasabah();

    lupaKirimOtp()
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', $nasabah->user->email)
        ->assertJsonPath('data.kedaluwarsa_menit', ResetPasswordOtp::TTL_MINUTES)
        ->assertJsonStructure(['message', 'data']);

    Mail::assertSent(OtpMail::class, fn (OtpMail $mail) => $mail->hasTo($nasabah->user->email)
        && strlen($mail->code) === 6);

    $this->assertDatabaseHas('password_reset_otp', ['nasabah_id' => $nasabah->id, 'attempts' => 0]);
});

it('menolak kirim otp dengan identitas atau pin yang salah', function () {
    lupaKirimOtp(['nis' => '99999999'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Identitas tidak ditemukan. Periksa kembali NIS dan No Rekening Anda.');

    lupaKirimOtp(['pin_keamanan' => '000000'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'PIN Keamanan salah.');

    Mail::assertNothingSent();
});

it('menolak kirim otp ketika email tidak sesuai', function () {
    lupaKirimOtp(['email' => 'orang.lain@student.bankmini.test'])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Email tidak sesuai dengan email akun nasabah.');

    Mail::assertNothingSent();
});

/*
|--------------------------------------------------------------------------
| Validasi OTP
|--------------------------------------------------------------------------
*/

it('validasi otp berhasil dan mengembalikan reset token', function () {
    lupaKirimOtp()->assertOk();
    $kode = lupaKodeOtp();

    $response = test()->postJson('/api/lupa-password/validasi-otp', [
        'nis' => '20241001',
        'code' => $kode,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.kedaluwarsa_menit', ResetPasswordOtp::TOKEN_TTL_MINUTES)
        ->assertJsonStructure(['data' => ['reset_token']]);

    expect($response->json('data.reset_token'))->toBeString()->not->toBeEmpty();
    $this->assertDatabaseMissing('password_reset_otp', ['nasabah_id' => lupaNasabah()->id]);
});

it('menghitung percobaan otp yang salah lalu mengunci sesi', function () {
    lupaKirimOtp();

    foreach ([1, 2] as $percobaan) {
        test()->postJson('/api/lupa-password/validasi-otp', [
            'nis' => '20241001',
            'code' => '000001',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Kode OTP salah. Sisa percobaan: '.(3 - $percobaan).'.');
    }

    test()->postJson('/api/lupa-password/validasi-otp', ['nis' => '20241001', 'code' => '000001'])
        ->assertStatus(423);

    $record = DB::table('password_reset_otp')->where('nasabah_id', lupaNasabah()->id)->first();

    expect((int) $record->attempts)->toBe(3)
        ->and($record->locked_until)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Simpan password baru
|--------------------------------------------------------------------------
*/

it('menyimpan password baru dan mencabut seluruh token lama', function () {
    $nasabah = lupaNasabah();
    $tokenLama = $nasabah->user->createToken('ponsel')->plainTextToken;

    lupaKirimOtp();
    $resetToken = test()->postJson('/api/lupa-password/validasi-otp', [
        'nis' => '20241001',
        'code' => lupaKodeOtp(),
    ])->json('data.reset_token');

    test()->postJson('/api/lupa-password/simpan-password', [
        'reset_token' => $resetToken,
        'password' => 'Baru@2026',
        'password_confirmation' => 'Baru@2026',
    ])->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('Baru@2026', $nasabah->user->fresh()->password))->toBeTrue();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'token' => hash('sha256', $tokenLama),
    ]);

    $this->assertDatabaseHas('user_notifications', [
        'user_id' => $nasabah->user->id,
        'judul' => 'Password Akun Diubah',
    ]);

    // Password baru harus bisa dipakai untuk login kembali.
    test()->postJson('/api/login', [
        'username' => '20241001',
        'password' => 'Baru@2026',
    ])->assertOk()
        ->assertJsonPath('user.username', '20241001');
});

it('menolak reset token yang tidak valid atau kadaluwarsa', function () {
    test()->postJson('/api/lupa-password/simpan-password', [
        'reset_token' => 'token-palsu',
        'password' => 'Baru@2026',
        'password_confirmation' => 'Baru@2026',
    ])->assertStatus(401)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Sesi reset password telah berakhir. Silakan mulai ulang proses.');
});

it('menolak reset token yang sudah lewat masa berlaku', function () {
    $nasabah = lupaNasabah();
    $token = app(ResetPasswordOtp::class)->issueToken($nasabah);

    test()->travel(ResetPasswordOtp::TOKEN_TTL_MINUTES + 1)->minutes();

    test()->postJson('/api/lupa-password/simpan-password', [
        'reset_token' => $token,
        'password' => 'Baru@2026',
        'password_confirmation' => 'Baru@2026',
    ])->assertStatus(401);
});

it('memvalidasi format password baru', function () {
    $resetToken = app(ResetPasswordOtp::class)->issueToken(lupaNasabah());

    test()->postJson('/api/lupa-password/simpan-password', [
        'reset_token' => $resetToken,
        'password' => 'pendek',
        'password_confirmation' => 'pendek',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Password baru minimal 8 karakter.');

    test()->postJson('/api/lupa-password/simpan-password', [
        'reset_token' => $resetToken,
        'password' => 'Baru@2026',
        'password_confirmation' => 'Beda@2026',
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['password']]);
});

it('alur lengkap lupa password tetap konsisten dengan versi web', function () {
    $nasabah = lupaNasabah();

    lupaKirimOtp()->assertOk();
    $resetToken = test()->postJson('/api/lupa-password/validasi-otp', [
        'nis' => '20241001',
        'code' => lupaKodeOtp(),
    ])->json('data.reset_token');

    test()->postJson('/api/lupa-password/simpan-password', [
        'reset_token' => $resetToken,
        'password' => 'Baru@2026',
        'password_confirmation' => 'Baru@2026',
    ])->assertOk();

    // Sesi lama harus ikut mati, dan akun tidak boleh menyisakan token aktif.
    expect($nasabah->user->fresh()->tokens()->count())->toBe(0)
        ->and(User::where('username', '20241001')->firstOrFail()->status)->toBe('active');

    // Notifikasi keamanan tercatat untuk nasabah.
    expect($nasabah->user->userNotifications()->where('judul', 'Password Akun Diubah')->exists())->toBeTrue();
});
