<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Nasabah;
use App\Support\ResetPasswordOtp;
use App\Support\ResetPasswordOtpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PasswordResetController extends Controller
{
    public function __construct(protected ResetPasswordOtp $otp) {}

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
            'email.email' => 'Format email tidak valid.',
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

            $payload = $this->otp->kirimOtp($nasabah);

            return response()->json([
                'success' => true,
                'message' => $payload['message'],
                'data' => array_filter([
                    'email' => $payload['email'],
                    'otp_dev' => $payload['otp_dev'] ?? null,
                    'kedaluwarsa_menit' => ResetPasswordOtp::TTL_MINUTES,
                ], fn ($value) => $value !== null),
            ]);
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
                'success' => false,
                'message' => 'Sesi tidak valid. Silakan mulai ulang permintaan.',
                'errors' => ['code' => ['Sesi tidak valid. Silakan mulai ulang permintaan.']],
            ], 422);
        }

        try {
            $this->otp->verifikasiOtp($nasabah, $data['code']);
        } catch (ResetPasswordOtpException $e) {
            return $this->otpError($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP berhasil diverifikasi.',
            'data' => [
                'reset_token' => $this->otp->issueToken($nasabah),
                'kedaluwarsa_menit' => ResetPasswordOtp::TOKEN_TTL_MINUTES,
            ],
        ]);
    }

    public function simpanPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'reset_token.required' => 'Sesi reset tidak ditemukan. Silakan ulangi proses.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();

        try {
            $nasabah = $this->otp->resolveToken($data['reset_token']);
        } catch (ResetPasswordOtpException $e) {
            return $this->otpError($e);
        }

        $this->otp->terapkanPasswordBaru($nasabah, $data['password']);

        // Password berubah, seluruh token lama tidak berlaku lagi.
        $nasabah->user->tokens()->delete();

        ActivityLog::catat($nasabah->user->id, 'reset_password', 'Nasabah mereset password melalui aplikasi mobile.');

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login dengan password baru.',
        ]);
    }

    private function otpError(ResetPasswordOtpException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'errors' => $e->errors(),
        ], $e->status);
    }

    private function validationError(\Illuminate\Contracts\Validation\Validator $validator): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors()->toArray(),
        ], 422);
    }
}
