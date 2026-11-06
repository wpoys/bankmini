<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseMessaging
{
    public static function send(User $user, string $judul, string $pesan, array $data = []): bool
    {
        $token = trim((string) $user->fcm_token);
        if ($token === '') {
            return false;
        }

        $credentials = self::credentials();
        if (! $credentials) {
            return false;
        }

        try {
            $accessToken = self::accessToken($credentials);
            if (! $accessToken) {
                return false;
            }

            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post('https://fcm.googleapis.com/v1/projects/'.$credentials['project_id'].'/messages:send', [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $judul,
                            'body' => $pesan,
                        ],
                        'data' => collect($data)->map(fn ($value) => (string) $value)->all(),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'konfirmasi_transaksi',
                                'sound' => 'default',
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 404) {
                $user->forceFill(['fcm_token' => null])->save();
            }

            Log::warning('Pengiriman FCM gagal.', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Pengiriman FCM gagal.', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        return false;
    }

    private static function credentials(): ?array
    {
        $path = (string) config('services.firebase.credentials');
        if ($path === '') {
            return null;
        }

        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            $path = base_path($path);
        }

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        $projectId = config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);

        if (! is_array($credentials)
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])
            || blank($projectId)) {
            return null;
        }

        $credentials['project_id'] = $projectId;

        return $credentials;
    }

    private static function accessToken(array $credentials): ?string
    {
        $cacheKey = 'firebase_access_token_'.sha1((string) $credentials['client_email']);
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $now = time();
        $header = self::base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = self::base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));
        $unsigned = $header.'.'.$claims;

        $signature = '';
        if (! openssl_sign($unsigned, $signature, (string) $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
            return null;
        }

        $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned.'.'.self::base64UrlEncode($signature),
        ]);

        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || $token === '') {
            Log::warning('Gagal memperoleh access token Firebase.', ['response' => $response->json()]);

            return null;
        }

        Cache::put($cacheKey, $token, now()->addMinutes(50));

        return $token;
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
