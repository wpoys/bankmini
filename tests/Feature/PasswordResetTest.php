<?php

use App\Http\Controllers\Auth\PasswordResetController;
use App\Mail\OtpMail;
use App\Models\Nasabah;
use App\Models\User;
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

function passwordResetNasabah(): Nasabah
{
    return Nasabah::with('user')->where('nis', '20241001')->firstOrFail();
}

function passwordResetRequestPayload(string $email = '20241001@student.bankmini.test', ?string $pin = null): array
{
    return [
        'email' => $email,
        'pin_keamanan' => $pin ?? '123456',
    ];
}

it('menampilkan halaman lupa password', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Lupa Password')
        ->assertSee('Email')
        ->assertDontSee('No Rekening')
        ->assertSee('PIN Keamanan')
        ->assertSee('Kirim OTP');
});

it('menolak kirim otp dengan identitas tidak ditemukan', function () {
    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload('missing@bankmini.test'))
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Akun dengan email tersebut tidak ditemukan.']);

    Mail::assertNothingSent();
});

it('menolak kirim otp dengan pin keamanan salah', function () {
    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload(pin: '000000'))
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'PIN Keamanan salah.']);

    Mail::assertNothingSent();
});

it('menolak kirim otp ketika user nasabah belum memiliki email', function () {
    User::where('username', '20241001')->update(['email' => null]);

    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload('20241001@student.bankmini.test'))
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'Akun dengan email tersebut tidak ditemukan.']);

    Mail::assertNothingSent();
});

it('mengirim otp ketika identitas valid', function () {
    $nasabah = passwordResetNasabah();

    $response = $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload())
        ->assertOk()
        ->assertJsonFragment([
            'email' => $nasabah->user->email,
        ])
        ->assertJsonPath('message', 'Kode OTP telah dikirim ke email Anda. Kode berlaku selama 5 menit.');

    Mail::assertSent(OtpMail::class, function (OtpMail $mail) use ($nasabah) {
        return $mail->hasTo($nasabah->user->email)
            && $mail->nama === $nasabah->nama_siswa
            && strlen($mail->code) === 6;
    });

    $this->assertDatabaseHas('password_reset_otp', [
        'nasabah_id' => $nasabah->id,
        'attempts' => 0,
    ]);
});

it('melakukan validasi otp dengan benar dan menghapus otp setelah dipakai', function () {
    $nasabah = passwordResetNasabah();

    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload());

    $code = null;
    Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $response = $this->postJson(route('password.validasi-otp'), [
        'email' => $nasabah->user->email,
        'code' => $code,
    ]);

    $response->assertOk()
        ->assertJsonPath('redirect', route('password.buat-password'));

    $this->assertDatabaseMissing('password_reset_otp', ['nasabah_id' => $nasabah->id]);
    $this->assertTrue(session()->has(PasswordResetController::SESSION_KEY));
});

it('menghitung tiga kali kesalahan otp lalu mengunci sesi', function () {
    $nasabah = passwordResetNasabah();

    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload());

    foreach ([1, 2] as $percobaan) {
        $this->postJson(route('password.validasi-otp'), [
            'email' => $nasabah->user->email,
            'code' => '000001',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Kode OTP salah. Sisa percobaan: '.(3 - $percobaan).'.');
    }

    $this->postJson(route('password.validasi-otp'), [
        'email' => $nasabah->user->email,
        'code' => '000001',
    ])->assertStatus(423);

    $record = DB::table('password_reset_otp')->where('nasabah_id', $nasabah->id)->first();
    $this->assertEquals(3, $record->attempts);
    $this->assertNotNull($record->locked_until);

    $this->postJson(route('password.validasi-otp'), [
        'email' => $nasabah->user->email,
        'code' => '000001',
    ])->assertStatus(423);
});

it('mengarahkan ke halaman buat password baru setelah otp terverifikasi', function () {
    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload());

    $code = null;
    Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $this->postJson(route('password.validasi-otp'), [
        'email' => passwordResetNasabah()->user->email,
        'code' => $code,
    ])->assertOk();

    $this->get(route('password.buat-password'))
        ->assertOk()
        ->assertSee('Buat Password Baru')
        ->assertSee('Budi Santoso');
});

it('menolak akses halaman buat password tanpa sesi valid', function () {
    $this->get(route('password.buat-password'))
        ->assertRedirect(route('password.request'));
});

it('menyimpan password baru dan menghapus sesi reset', function () {
    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload());

    $code = null;
    Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $this->postJson(route('password.validasi-otp'), [
        'email' => passwordResetNasabah()->user->email,
        'code' => $code,
    ])->assertOk();

    $this->postJson(route('password.simpan'), [
        'password' => 'Baru@2026',
        'password_confirmation' => 'Baru@2026',
    ])->assertOk()
        ->assertJsonPath('redirect', route('login'));

    $user = User::where('username', '20241001')->firstOrFail();
    $this->assertTrue(Hash::check('Baru@2026', $user->password));
    $this->assertFalse(session()->has(PasswordResetController::SESSION_KEY));
});

it('menolak simpan password tanpa sesi valid', function () {
    $this->postJson(route('password.simpan'), [
        'password' => 'Baru@2026',
        'password_confirmation' => 'Baru@2026',
    ])->assertStatus(401);
});

it('memvalidasi input otp, password wajib konfirmasi cocok', function () {
    $this->postJson(route('password.kirim-otp'), passwordResetRequestPayload());

    $code = null;
    Mail::assertSent(OtpMail::class, function (OtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    $this->postJson(route('password.validasi-otp'), [
        'email' => passwordResetNasabah()->user->email,
        'code' => $code,
    ])->assertOk();

    $this->postJson(route('password.simpan'), [
        'password' => 'Baru@2026',
        'password_confirmation' => 'Beda@2026',
    ])->assertStatus(422)
        ->assertJsonStructure(['errors' => ['password']]);
});
