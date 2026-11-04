<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AktivasiController extends Controller
{
    private const KODE_MENIT = 5;
    private const MAKS_KIRIM_ULANG = 5;

    public function mintaKode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nis' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:191'],
            'jenis_tabungan' => ['required', 'in:siswa,guru'],
        ]);
        $user = User::with('nasabah')->where('username', $data['nis'])->first();
        if (! $user || ! $user->isNasabah() || $user->email !== $data['email'] ||
            $user->nasabah?->produk !== $data['jenis_tabungan']) {
            return $this->gagalUmum();
        }
        if ($user->status_aktivasi === User::AKTIVASI_AKTIF) {
            return response()->json(['success' => false, 'code' => 'ACCOUNT_ALREADY_ACTIVE', 'message' => 'Akun sudah aktif. Silakan masuk melalui Login.'], 409);
        }

        return $this->terbitkanKode($user);
    }

    public function verifikasiKode(Request $request): JsonResponse
    {
        $data = $request->validate(['nis' => ['required', 'string'], 'kode' => ['required', 'digits:6']]);
        $user = User::with('nasabah')->where('username', $data['nis'])->first();
        if (! $user || ! $user->isNasabah()) return $this->gagalUmum();
        if ($user->aktivasi_dikunci_sampai?->isFuture()) {
            return response()->json(['success' => false, 'code' => 'ACTIVATION_LOCKED', 'message' => 'Akun terkunci sementara. Coba lagi setelah masa kunci berakhir.', 'data' => ['sisa_kunci_detik' => now()->diffInSeconds($user->aktivasi_dikunci_sampai)]], 423);
        }
        if (! $user->kode_aktivasi_hash || ! $user->kode_aktivasi_kadaluarsa?->isFuture() ||
            ! Hash::check($data['kode'], $user->kode_aktivasi_hash)) {
            $user->increment('percobaan_aktivasi');
            $user->refresh();
            $sisa = max(0, 3 - (int) $user->percobaan_aktivasi);
            if ($sisa === 0) $user->update(['aktivasi_dikunci_sampai' => now()->addHour()]);
            ActivityLog::catat($user->id, 'aktivasi_verifikasi_gagal', 'Verifikasi kode aktivasi gagal.');
            return response()->json(['success' => false, 'code' => $sisa === 0 ? 'ACTIVATION_LOCKED' : 'INVALID_ACTIVATION_CODE', 'message' => $sisa === 0 ? 'Akun terkunci sementara selama 1 jam.' : 'Kode verifikasi salah.', 'data' => ['sisa_percobaan' => $sisa]], 422);
        }

        $token = Str::random(64);
        $user->update([
            'token_aktivasi_hash' => Hash::make($token),
            'token_aktivasi_kadaluarsa' => now()->addMinutes(15),
            'kode_aktivasi_hash' => null,
            'kode_aktivasi_kadaluarsa' => null,
            'percobaan_aktivasi' => 0,
        ]);
        ActivityLog::catat($user->id, 'aktivasi_verifikasi_berhasil', 'Kode aktivasi berhasil diverifikasi.');
        return response()->json(['success' => true, 'data' => ['token_sementara' => $token]]);
    }

    public function kirimUlang(Request $request): JsonResponse
    {
        $data = $request->validate(['nis' => ['required', 'string']]);
        $user = User::where('username', $data['nis'])->first();
        if (! $user || ! $user->isNasabah()) return $this->gagalUmum();
        if ($user->aktivasi_kirim_ulang_sampai?->isFuture()) {
            return response()->json(['success' => false, 'code' => 'RESEND_COOLDOWN', 'message' => 'Tunggu sampai jeda kirim ulang selesai.'], 429);
        }
        if ($user->jumlah_kirim_ulang >= self::MAKS_KIRIM_ULANG) {
            return response()->json(['success' => false, 'message' => 'Batas kirim ulang kode tercapai.'], 429);
        }
        return $this->terbitkanKode($user);
    }

    public function simpanKredensial(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nis' => ['required', 'string'],
            'token_sementara' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/\d/', 'regex:/[^A-Za-z0-9]/'],
            'pin' => ['required', 'digits:6', 'confirmed', 'not_regex:/^(\d)\1{5}$/', 'not_in:123456,654321'],
        ], ['password.regex' => 'Sandi harus memuat huruf besar, kecil, angka, dan karakter khusus.']);
        $user = User::with('nasabah')->where('username', $data['nis'])->first();
        if (! $user || ! $user->isNasabah() || ! $user->token_aktivasi_kadaluarsa?->isFuture() ||
            ! Hash::check($data['token_sementara'], (string) $user->token_aktivasi_hash)) {
            return response()->json(['success' => false, 'message' => 'Token aktivasi tidak valid atau sudah kedaluwarsa.'], 422);
        }
        $nomorTelepon = preg_replace('/\D+/', '', (string) $user->nasabah?->no_hp);
        if ($nomorTelepon !== '' && ($data['pin'] === $nomorTelepon || str_ends_with($nomorTelepon, $data['pin']))) {
            return response()->json(['success' => false, 'message' => 'PIN tidak boleh sama dengan nomor telepon nasabah.'], 422);
        }
        DB::transaction(function () use ($user, $data): void {
            $user->update(['password' => Hash::make($data['password']), 'status_aktivasi' => User::AKTIVASI_AKTIF, 'token_aktivasi_hash' => null, 'token_aktivasi_kadaluarsa' => null]);
            $user->nasabah?->update(['pin_keamanan' => Hash::make($data['pin'])]);
        });
        ActivityLog::catat($user->id, 'aktivasi_berhasil', 'Aktivasi akun berhasil diselesaikan.');
        $token = $user->createToken('mobile-app')->plainTextToken;
        return response()->json(['success' => true, 'message' => 'Aktivasi berhasil.', 'data' => ['token' => $token, 'user' => ApiFormatter::user($user)]]);
    }

    private function terbitkanKode(User $user): JsonResponse
    {
        $kode = (string) random_int(100000, 999999);
        $user->update([
            'kode_aktivasi_hash' => Hash::make($kode),
            'kode_aktivasi_kadaluarsa' => now()->addMinutes(self::KODE_MENIT),
            'percobaan_aktivasi' => 0,
            'aktivasi_kirim_ulang_sampai' => now()->addSeconds(60),
            'jumlah_kirim_ulang' => $user->jumlah_kirim_ulang + 1,
        ]);
        Mail::raw("Kode aktivasi Bank Mini Anda: {$kode}. Berlaku 5 menit.", fn ($mail) => $mail->to($user->email)->subject('Kode Aktivasi Bank Mini'));
        ActivityLog::catat($user->id, 'aktivasi_kode_diterbitkan', 'Kode aktivasi diterbitkan melalui email.');
        return response()->json(['success' => true, 'message' => 'Kode aktivasi telah dikirim.', 'data' => ['email_tersamarkan' => $this->samarkan($user->email), 'kedaluwarsa_pada' => $user->kode_aktivasi_kadaluarsa->toIso8601String(), 'sisa_percobaan' => 3]]);
    }

    private function gagalUmum(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Data aktivasi tidak cocok atau belum terdaftar.'], 422);
    }

    private function samarkan(?string $email): string
    {
        if (! $email) return '';
        [$nama, $domain] = explode('@', $email, 2);
        return substr($nama, 0, 1).'***@'.$domain;
    }
}
