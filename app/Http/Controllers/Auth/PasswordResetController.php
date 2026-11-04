<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Support\ResetPasswordOtp;
use App\Support\ResetPasswordOtpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public const OTP_TTL_MINUTES = ResetPasswordOtp::TTL_MINUTES;

    public const MAX_ATTEMPTS = ResetPasswordOtp::MAX_ATTEMPTS;

    public const LOCK_MINUTES = ResetPasswordOtp::LOCK_MINUTES;

    public const SESSION_KEY = 'password_reset_verified';

    public function __construct(protected ResetPasswordOtp $otp) {}

    public function showLinkRequestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function kirimOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nis' => ['required', 'string', 'max:30'],
            'no_rekening' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email'],
            'pin_keamanan' => ['required', 'string'],
        ], [
            'nis.required' => 'NIS wajib diisi.',
            'no_rekening.required' => 'No Rekening wajib diisi.',
            'pin_keamanan.required' => 'PIN Keamanan wajib diisi.',
            'email.email' => 'Format salah! Mohon masukkan alamat email yang valid dan ubah teks Anda.',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();

        try {
            $nasabah = $this->otp->verifikasiIdentitas(
                $data['nis'],
                $data['no_rekening'],
                $data['pin_keamanan'],
                $data['email'] ?? null,
            );

            return response()->json($this->otp->kirimOtp($nasabah));
        } catch (ResetPasswordOtpException $e) {
            return $this->otpError($e);
        }
    }

    public function validasiOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nis' => ['required', 'string', 'max:30'],
            'code' => ['required', 'digits:6'],
        ], [
            'nis.required' => 'NIS wajib diisi.',
            'code.required' => 'Kode OTP wajib diisi.',
            'code.digits' => 'Kode OTP harus 6 digit.',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();

        $nasabah = Nasabah::with('user')->where('nis', $data['nis'])->first();

        if (! $nasabah) {
            return response()->json([
                'message' => 'Sesi tidak valid. Silakan mulai ulang permintaan.',
                'errors' => ['code' => 'Sesi tidak valid. Silakan mulai ulang permintaan.'],
            ], 422);
        }

        try {
            $this->otp->verifikasiOtp($nasabah, $data['code']);
        } catch (ResetPasswordOtpException $e) {
            return $this->otpError($e);
        }

        $request->session()->put(self::SESSION_KEY, [
            'nasabah_id' => $nasabah->id,
            'nis' => $nasabah->nis,
            'nama' => $nasabah->nama_siswa,
        ]);

        return response()->json([
            'message' => 'Kode OTP berhasil diverifikasi.',
            'redirect' => route('password.buat-password'),
        ]);
    }

    public function showBuatPassword(Request $request): View|RedirectResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);

        if (! $session || ! isset($session['nasabah_id'])) {
            return redirect()->route('password.request')
                ->withErrors(['identitas' => 'Sesi tidak valid. Silakan mulai ulang proses.']);
        }

        return view('auth.buat-password-baru', ['nis' => $session['nis'], 'nama' => $session['nama']]);
    }

    public function simpanPassword(Request $request): JsonResponse
    {
        $session = $request->session()->get(self::SESSION_KEY);

        if (! $session || ! isset($session['nasabah_id'])) {
            return response()->json([
                'message' => 'Sesi tidak valid. Silakan mulai ulang proses.',
                'errors' => ['session' => 'Sesi reset password telah berakhir.'],
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();

        $nasabah = Nasabah::with('user')->find($session['nasabah_id']);

        if (! $nasabah || ! $nasabah->user) {
            return response()->json([
                'message' => 'Akun nasabah tidak ditemukan.',
                'errors' => ['session' => 'Akun tidak ditemukan.'],
            ], 404);
        }

        $this->otp->terapkanPasswordBaru($nasabah, $data['password']);

        $request->session()->forget(self::SESSION_KEY);

        return response()->json([
            'message' => 'Password berhasil diperbarui. Silakan login dengan password baru.',
            'redirect' => route('login'),
        ]);
    }

    protected function otpError(ResetPasswordOtpException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], $e->status);
    }

    protected function validationError(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        return response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 422);
    }
}
