<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::with('nasabah')->where('username', $request->username)->first();

        if ($user?->isNasabah() && $user->status_aktivasi !== User::AKTIVASI_AKTIF) {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_NOT_ACTIVATED',
                'message' => 'Akun Anda belum diaktifkan. Pilih Nasabah Baru untuk mengaktifkan.',
            ], 403);
        }

        // Login menerima password akun ATAU PIN keamanan 6 digit nasabah,
        // karena aplikasi mobile E-Teller (Flutter) login dengan NIS + PIN.
        $sandiCocok = $user !== null && (Hash::check($request->password, $user->password)
            || ($user->nasabah !== null
                && Hash::check($request->password, (string) $user->nasabah->pin_keamanan)));

        if (! $sandiCocok) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password salah.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda tidak aktif. Hubungi admin.'],
            ]);
        }

        if ($user->role !== User::ROLE_NASABAH) {
            throw ValidationException::withMessages([
                'username' => ['Aplikasi ini khusus untuk nasabah.'],
            ]);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $this->formatUser($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('nasabah');

        return response()->json([
            'success' => true,
            'user' => $this->formatUser($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    private function formatUser(User $user): array
    {
        $data = ApiFormatter::user($user);

        if ($user->nasabah) {
            $data['nasabah'] = ApiFormatter::nasabah($user->nasabah);
        }

        return $data;
    }
}
