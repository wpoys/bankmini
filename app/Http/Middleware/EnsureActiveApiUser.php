<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveApiUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->status !== 'active') {
            return response()->json([
                'success' => false,
                'code' => 'ACCOUNT_DISABLED',
                'message' => 'Akun Anda dinonaktifkan. Hubungi Administrator.',
            ], 403);
        }

        if ($request->user()?->isNasabah() && $request->user()->status_aktivasi !== 'aktif') {
            return response()->json([
                'success' => false,
                'code' => 'SETUP_REQUIRED',
                'message' => 'Aktivasi akun harus diselesaikan terlebih dahulu.',
            ], 403);
        }

        return $next($request);
    }
}
