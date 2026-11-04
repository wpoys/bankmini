<?php

namespace App\Support;

use App\Mail\OtpMail;
use App\Models\Nasabah;
use App\Models\UserNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ResetPasswordOtp
{
    public const TTL_MINUTES = 5;

    public const MAX_ATTEMPTS = 3;

    public const LOCK_MINUTES = 15;

    public const TOKEN_TTL_MINUTES = 10;

    /**
     * Cocokkan identitas nasabah (NIS + No Rekening + PIN Keamanan) dan
     * pastikan akunnya punya email terdaftar.
     */
    public function verifikasiIdentitas(string $nis, string $noRekening, string $pin, ?string $email = null): Nasabah
    {
        $nasabah = Nasabah::with('user')
            ->where('nis', $nis)
            ->where('no_rekening', $noRekening)
            ->first();

        if (! $nasabah) {
            throw new ResetPasswordOtpException(
                'Identitas tidak ditemukan. Periksa kembali NIS dan No Rekening Anda.',
                'identitas',
                422,
                'Data nasabah tidak ditemukan.',
            );
        }

        if (! $nasabah->pin_keamanan || ! Hash::check($pin, $nasabah->pin_keamanan)) {
            throw new ResetPasswordOtpException(
                'PIN Keamanan salah.',
                'pin_keamanan',
                422,
                'PIN Keamanan tidak sesuai.',
            );
        }

        $user = $nasabah->user ?? $nasabah->ensureUser();
        $nasabah->setRelation('user', $user);

        if (! $user->email) {
            throw new ResetPasswordOtpException(
                'Akun nasabah ini belum memiliki email terdaftar. Hubungi Administrator.',
                'email',
            );
        }

        if ($email !== null && $email !== '' && strcasecmp($user->email, $email) !== 0) {
            throw new ResetPasswordOtpException(
                'Email tidak sesuai dengan email akun nasabah.',
                'email',
            );
        }

        return $nasabah;
    }

    /**
     * Buat kode OTP baru, simpan hash-nya, lalu kirim ke email nasabah.
     */
    public function kirimOtp(Nasabah $nasabah): array
    {
        $user = $nasabah->user ?? $nasabah->ensureUser();
        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_otp')
            ->where('nasabah_id', $nasabah->id)
            ->delete();

        DB::table('password_reset_otp')->insert([
            'nasabah_id' => $nasabah->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
            'locked_until' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($user->email)->send(new OtpMail($code, $nasabah->nama_siswa));
        } catch (Throwable $e) {
            Log::error('Gagal mengirim email OTP via SMTP.', [
                'recipient' => $user->email,
                'nasabah_id' => $nasabah->id,
                'mailer' => config('mail.default'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            DB::table('password_reset_otp')
                ->where('nasabah_id', $nasabah->id)
                ->delete();

            throw new ResetPasswordOtpException(
                'Kode OTP gagal dikirim. Silakan coba lagi beberapa saat.',
                'email',
                500,
            );
        }

        Log::info('OTP email dispatch attempted.', [
            'recipient' => $user->email,
            'nasabah_id' => $nasabah->id,
            'mailer' => config('mail.default'),
            'expires_at' => DB::table('password_reset_otp')
                ->where('nasabah_id', $nasabah->id)
                ->value('expires_at'),
        ]);

        $payload = [
            'message' => 'Kode OTP telah dikirim ke email Anda. Kode berlaku selama '.self::TTL_MINUTES.' menit.',
            'email' => $user->email,
        ];

        if (config('mail.otp_dev_display') && app()->environment('local')) {
            $payload['otp_dev'] = $code;
        }

        return $payload;
    }

    /**
     * Cocokkan kode OTP. Record dihapus bila berhasil.
     */
    public function verifikasiOtp(Nasabah $nasabah, string $code): void
    {
        $record = DB::table('password_reset_otp')
            ->where('nasabah_id', $nasabah->id)
            ->first();

        if (! $record || Carbon::parse($record->expires_at)->isPast()) {
            DB::table('password_reset_otp')->where('nasabah_id', $nasabah->id)->delete();

            throw new ResetPasswordOtpException(
                'Kode OTP kedaluwarsa. Silakan minta kode baru.',
                'code',
                422,
                'Kode OTP sudah kedaluwarsa.',
            );
        }

        if ($record->locked_until && Carbon::parse($record->locked_until)->isFuture()) {
            $remaining = ceil(now()->diffInMinutes(Carbon::parse($record->locked_until), true));

            throw new ResetPasswordOtpException(
                'Terlalu banyak percobaan. Coba lagi dalam '.$remaining.' menit.',
                'code',
                423,
                'Sesi terkunci sementara. Coba lagi nanti.',
            );
        }

        if (! Hash::check($code, $record->code)) {
            $attempts = $record->attempts + 1;

            if ($attempts >= self::MAX_ATTEMPTS) {
                DB::table('password_reset_otp')
                    ->where('nasabah_id', $nasabah->id)
                    ->update([
                        'attempts' => $attempts,
                        'locked_until' => now()->addMinutes(self::LOCK_MINUTES),
                        'updated_at' => now(),
                    ]);

                throw new ResetPasswordOtpException(
                    'Kode OTP salah '.self::MAX_ATTEMPTS.' kali. Sesi dikunci selama '.self::LOCK_MINUTES.' menit.',
                    'code',
                    423,
                    'Terlalu banyak percobaan salah. Sesi dikunci sementara.',
                );
            }

            DB::table('password_reset_otp')
                ->where('nasabah_id', $nasabah->id)
                ->update([
                    'attempts' => $attempts,
                    'updated_at' => now(),
                ]);

            throw new ResetPasswordOtpException(
                'Kode OTP salah. Sisa percobaan: '.(self::MAX_ATTEMPTS - $attempts).'.',
                'code',
                422,
                'Kode OTP tidak sesuai.',
            );
        }

        $user = $nasabah->user ?? $nasabah->ensureUser();

        if (! $user->email) {
            throw new ResetPasswordOtpException(
                'Akun nasabah ini belum memiliki email terdaftar. Hubungi Administrator.',
                'email',
            );
        }

        $nasabah->setRelation('user', $user);

        DB::table('password_reset_otp')->where('nasabah_id', $nasabah->id)->delete();
    }

    /**
     * Token sementara untuk klien non-browser (mis. aplikasi mobile) yang
     * tidak memiliki session setelah OTP terverifikasi.
     */
    public function issueToken(Nasabah $nasabah): string
    {
        return Crypt::encryptString(json_encode([
            'nasabah_id' => $nasabah->id,
            'exp' => now()->addMinutes(self::TOKEN_TTL_MINUTES)->getTimestamp(),
        ], JSON_THROW_ON_ERROR));
    }

    public function resolveToken(string $token): Nasabah
    {
        $invalid = new ResetPasswordOtpException(
            'Sesi reset password telah berakhir. Silakan mulai ulang proses.',
            'session',
            401,
            'Sesi reset password telah berakhir.',
        );

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw $invalid;
        }

        if (! is_array($payload) || ! isset($payload['nasabah_id'], $payload['exp'])) {
            throw $invalid;
        }

        if (Carbon::createFromTimestamp((int) $payload['exp'])->isPast()) {
            throw $invalid;
        }

        $nasabah = Nasabah::with('user')->find($payload['nasabah_id']);

        if (! $nasabah || ! $nasabah->user) {
            throw $invalid;
        }

        return $nasabah;
    }

    /**
     * Simpan password baru untuk akun nasabah dan beri notifikasi keamanan.
     */
    public function terapkanPasswordBaru(Nasabah $nasabah, string $password): UserNotification
    {
        $user = $nasabah->user;

        $user->password = $password;
        $user->save();

        DB::table('password_reset_otp')->where('nasabah_id', $nasabah->id)->delete();

        return UserNotification::kirim(
            $user,
            UserNotification::TIPE_WARNING,
            'Password Akun Diubah',
            'Password akun Anda baru saja direset. Jika bukan Anda yang melakukannya, segera hubungi pihak bank.',
            route('login'),
        );
    }
}
