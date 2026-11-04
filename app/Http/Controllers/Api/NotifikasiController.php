<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Support\ApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    private const PER_HALAMAN = 20;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $notifikasi = $request->user()
            ->userNotifications()
            ->orderByDesc('read_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? self::PER_HALAMAN)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $notifikasi->getCollection()
                ->map(fn (UserNotification $n) => ApiFormatter::notifikasi($n))
                ->values(),
            'meta' => array_merge(ApiFormatter::pagination($notifikasi), [
                'belum_dibaca' => $request->user()->userNotifications()->belumDibaca()->count(),
            ]),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'belum_dibaca' => $request->user()->userNotifications()->belumDibaca()->count(),
            ],
        ]);
    }

    public function baca(Request $request, UserNotification $notifikasi): JsonResponse
    {
        if (! $this->punya($request, $notifikasi)) {
            return $this->gagal();
        }

        $notifikasi->tandaiDibaca();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'data' => ApiFormatter::notifikasi($notifikasi->refresh()),
        ]);
    }

    public function bacaSemua(Request $request): JsonResponse
    {
        $jumlah = $request->user()
            ->userNotifications()
            ->belumDibaca()
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $jumlah > 0
                ? 'Semua notifikasi telah ditandai dibaca.'
                : 'Tidak ada notifikasi yang belum dibaca.',
            'data' => ['belum_dibaca' => 0],
        ]);
    }

    public function hapus(Request $request, UserNotification $notifikasi): JsonResponse
    {
        if (! $this->punya($request, $notifikasi)) {
            return $this->gagal();
        }

        $notifikasi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus.',
            'data' => [
                'belum_dibaca' => $request->user()->userNotifications()->belumDibaca()->count(),
            ],
        ]);
    }

    private function punya(Request $request, UserNotification $notifikasi): bool
    {
        return $notifikasi->user_id === $request->user()->id;
    }

    private function gagal(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke notifikasi ini.',
        ], 403);
    }
}
