<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->status !== 'active' || $user->role === 'nasabah')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->role === 'nasabah'
                ? 'Login nasabah hanya tersedia melalui aplikasi E-Teller.'
                : 'Akun Anda dinonaktifkan. Hubungi Administrator.';

            return redirect()->route('login')->withErrors([
                'username' => $message,
            ]);
        }

        return $next($request);
    }
}
