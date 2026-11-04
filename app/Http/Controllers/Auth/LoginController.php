<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username/email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        $user = User::where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Username/email atau password salah.']);
        }

        if ($user->status !== 'active') {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Akun Anda dinonaktifkan. Hubungi Administrator.']);
        }

        if ($user->role === User::ROLE_NASABAH) {
            return back()
                ->withInput($request->only('username'))
                ->withErrors(['username' => 'Login nasabah hanya tersedia melalui aplikasi E-Teller.']);
        }

        Auth::login($user, $remember);

        $request->session()->regenerate();

        ActivityLog::catat($user->id, 'login', "{$user->nama_petugas} masuk ke sistem.");

        return redirect()->intended($this->dashboardByRole($user->role));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            ActivityLog::catat($user->id, 'logout', "{$user->nama_petugas} keluar dari sistem.");
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    protected function dashboardByRole(string $role): string
    {
        return match ($role) {
            'admin' => route('admin.dashboard'),
            'teller' => route('teller.dashboard'),
            'supervisor' => route('supervisor.dashboard'),
            default => route('login'),
        };
    }
}
