<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Konfigurasi;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\KodeAkun;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OtorisasiNasabahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isNasabah() && $user->nasabah, 403);

        $this->expireStale($user->nasabah->id);

        $page = max(1, (int) $request->query('page', 1));
        $query = Transaksi::where('id_nasabah', $user->nasabah->id);
        if (in_array($request->query('status'), ['menunggu', 'pending'], true)) {
            $query->where('status', Transaksi::STATUS_PENDING);
        } elseif ($request->filled('status')) {
            $status = $request->query('status') === 'kedaluwarsa'
                ? Transaksi::STATUS_KEDALUWARSA
                : $request->query('status');
            $query->where('status', $status);
        }
        $items = $query->latest('id')->paginate(15, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'Daftar otorisasi.',
            'data' => $items->getCollection()->map(fn ($trx) => $this->format($trx)),
            'meta' => [
                'page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $trx = $this->owned($request, $id);
        $this->expireIfDue($trx);

        return response()->json([
            'success' => true,
            'message' => 'Detail otorisasi.',
            'data' => $this->format($trx->fresh()),
            'meta' => (object) [],
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['pin' => ['required', 'digits:6']]);
        $user = $request->user();
        abort_unless($user->isNasabah() && $user->nasabah, 403);

        $result = DB::transaction(function () use ($request, $id, $user) {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $nasabah = $account->nasabah()->lockForUpdate()->firstOrFail();
            $trx = Transaksi::whereKey($id)->lockForUpdate()->first();

            if (! $trx || (int) $trx->id_nasabah !== (int) $nasabah->id) {
                return ['message' => 'Transaksi tidak ditemukan.', 'code' => 404];
            }

            if ($this->expireIfDue($trx)) {
                return ['message' => 'Otorisasi telah kedaluwarsa.', 'code' => 410];
            }

            if ($trx->status !== Transaksi::STATUS_PENDING) {
                return ['message' => 'Transaksi tidak lagi menunggu.', 'code' => 409];
            }

            if ($account->pin_auth_admin_lock) {
                return ['message' => 'Verifikasi PIN terkunci. Hubungi admin untuk membuka.', 'code' => 423, 'data' => ['attempts_left' => 0, 'admin_lock' => true]];
            }

            if ($account->pin_auth_locked_until && now()->lt($account->pin_auth_locked_until)) {
                return [
                    'message' => 'Verifikasi PIN terkunci sementara. Coba lagi setelah waktu kunci berakhir.',
                    'code' => 423,
                    'data' => ['attempts_left' => 0, 'locked_until' => $account->pin_auth_locked_until->toISOString(), 'admin_lock' => false],
                ];
            }

            if (! Hash::check($request->input('pin'), $nasabah->pin_keamanan)) {
                return $this->recordWrongPin($account, $trx);
            }

            if ($trx->jenis_trans === Transaksi::JENIS_PENARIKAN && ($nasabah->saldo - $trx->nominal < Konfigurasi::saldoMinimum())) {
                $trx->status = Transaksi::STATUS_DITOLAK;
                $trx->save();
                UserNotification::kirim(
                    $trx->id_user,
                    UserNotification::TIPE_WARNING,
                    'Otorisasi Dibatalkan',
                    'Transaksi '.$trx->no_struk.' dibatalkan karena saldo nasabah tidak mencukupi atau berada di bawah batas minimum.',
                    null,
                );

                return ['message' => 'Saldo tidak mencukupi atau di bawah saldo minimum.', 'code' => 422];
            }

            $account->forceFill(['pin_auth_fail_count' => 0, 'pin_auth_locked_until' => null])->save();
            $trx->status = Transaksi::STATUS_APPROVED;
            $trx->approved_by = $account->id;
            $trx->approved_at = now();
            $trx->save();
            $nasabah->saldo += $trx->jenis_trans === Transaksi::JENIS_SETORAN ? $trx->nominal : -$trx->nominal;
            $nasabah->save();
            $this->journal($trx);
            ActivityLog::catat($account->id, 'otorisasi_transaksi', 'Menyetujui transaksi '.$trx->no_struk);
            UserNotification::kirim(
                $trx->id_user,
                UserNotification::TIPE_SUCCESS,
                'Transaksi Disetujui',
                'Nasabah menyetujui transaksi '.$trx->no_struk.' sebesar '.$trx->nominal_rupiah.'.',
                null,
            );

            return ['transaksi' => $trx->fresh()];
        });

        if (isset($result['message'])) {
            return $this->failure($result['message'], $result['code'], $result['data'] ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi disetujui.',
            'data' => $this->format($result['transaksi']),
            'meta' => (object) [],
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isNasabah() && $user->nasabah, 403);

        $result = DB::transaction(function () use ($id, $user) {
            $trx = Transaksi::whereKey($id)->lockForUpdate()->first();
            if (! $trx || (int) $trx->id_nasabah !== (int) $user->nasabah->id) {
                return ['message' => 'Transaksi tidak ditemukan.', 'code' => 404];
            }

            if ($this->expireIfDue($trx)) {
                return ['message' => 'Otorisasi telah kedaluwarsa.', 'code' => 410];
            }

            if ($trx->status !== Transaksi::STATUS_PENDING) {
                return ['message' => 'Transaksi tidak lagi menunggu.', 'code' => 409];
            }

            $trx->status = Transaksi::STATUS_DITOLAK;
            $trx->save();
            ActivityLog::catat($user->id, 'tolak_transaksi', 'Menolak transaksi '.$trx->no_struk);
            UserNotification::kirim(
                $trx->id_user,
                UserNotification::TIPE_WARNING,
                'Transaksi Ditolak',
                'Nasabah menolak transaksi '.$trx->no_struk.'.',
                null,
            );

            return ['transaksi' => $trx->fresh()];
        });

        if (isset($result['message'])) {
            return $this->failure($result['message'], $result['code']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi ditolak.',
            'data' => $this->format($result['transaksi']),
            'meta' => (object) [],
        ]);
    }

    private function owned(Request $request, int $id): Transaksi
    {
        $user = $request->user();
        abort_unless($user->isNasabah() && $user->nasabah, 403);

        return Transaksi::where('id_nasabah', $user->nasabah->id)->findOrFail($id);
    }

    private function recordWrongPin(User $account, Transaksi $trx): array
    {
        $account->pin_auth_fail_count++;
        $trx->otorisasi_percobaan++;

        if ($account->pin_auth_fail_count < 3) {
            $account->save();
            $trx->save();

            return [
                'message' => 'PIN tidak sesuai. Sisa '.max(0, 3 - $account->pin_auth_fail_count).' percobaan.',
                'code' => 422,
                'data' => [
                    'attempts_left' => max(0, 3 - $account->pin_auth_fail_count),
                    'locked_until' => null,
                    'admin_lock' => false,
                ],
            ];
        }

        $account->pin_auth_fail_count = 0;
        $account->pin_auth_lock_level++;
        $account->pin_auth_locked_until = match ($account->pin_auth_lock_level) {
            1 => now()->addMinutes(15),
            2 => now()->addHour(),
            default => null,
        };
        $account->pin_auth_admin_lock = $account->pin_auth_lock_level >= 3;

        $trx->status = Transaksi::STATUS_DITOLAK;
        $trx->save();
        $account->save();

        UserNotification::kirim(
            $trx->id_user,
            UserNotification::TIPE_DANGER,
            'Otorisasi Dibatalkan',
            'Transaksi '.$trx->no_struk.' dibatalkan setelah tiga percobaan PIN gagal.',
            null,
        );
        ActivityLog::catat($account->id, 'pin_otorisasi_terkunci', 'Verifikasi PIN terkunci setelah tiga percobaan gagal.');

        $message = $account->pin_auth_admin_lock
            ? 'PIN salah tiga kali. Verifikasi terkunci dan hanya dapat dibuka oleh admin.'
            : 'PIN salah tiga kali. Permintaan dibatalkan dan verifikasi PIN dikunci sementara.';

        return [
            'message' => $message,
            'code' => 423,
            'data' => [
                'attempts_left' => 0,
                'locked_until' => $account->pin_auth_locked_until?->toISOString(),
                'admin_lock' => $account->pin_auth_admin_lock,
            ],
        ];
    }

    private function expireStale(int $nasabahId): void
    {
        Transaksi::where('id_nasabah', $nasabahId)
            ->where('status', Transaksi::STATUS_PENDING)
            ->where(function ($query) {
                $query->where('kadaluarsa_pada', '<=', now())
                    ->orWhere(function ($query) {
                        $query->whereNull('kadaluarsa_pada')
                            ->where('created_at', '<=', now()->subMinutes(5));
                    });
            })
            ->get()
            ->each(fn (Transaksi $trx) => $this->expireIfDue($trx));
    }

    private function expireIfDue(Transaksi $trx): bool
    {
        if ($trx->status !== Transaksi::STATUS_PENDING) {
            return false;
        }

        $expiry = $trx->kadaluarsa_pada ?? $trx->created_at?->copy()->addMinutes(5);
        if (! $expiry || now()->lt($expiry)) {
            return false;
        }

        $updated = Transaksi::whereKey($trx->id)
            ->where('status', Transaksi::STATUS_PENDING)
            ->update(['status' => Transaksi::STATUS_KEDALUWARSA, 'updated_at' => now()]);

        if (! $updated) {
            return false;
        }

        $trx->refresh();
        UserNotification::kirim(
            $trx->id_user,
            UserNotification::TIPE_WARNING,
            'Otorisasi Kedaluwarsa',
            'Permintaan transaksi '.$trx->no_struk.' kedaluwarsa karena tidak dikonfirmasi dalam 5 menit.',
            null,
        );
        ActivityLog::catat($trx->id_user, 'otorisasi_kedaluwarsa', 'Permintaan transaksi '.$trx->no_struk.' kedaluwarsa.');

        return true;
    }

    private function format(Transaksi $trx): array
    {
        return [
            'id' => $trx->id,
            'status' => match ($trx->status) {
                Transaksi::STATUS_PENDING => 'menunggu',
                Transaksi::STATUS_KEDALUWARSA => 'kedaluwarsa',
                default => $trx->status,
            },
            'jenis' => $trx->jenis_trans,
            'jumlah' => $trx->nominal,
            'deskripsi' => $trx->catatan ?: $trx->jenis_label.' - '.$trx->nominal_rupiah,
            'dibuat_pada' => $trx->created_at?->toISOString(),
            'kadaluarsa_pada' => ($trx->kadaluarsa_pada ?? $trx->created_at?->copy()->addMinutes(5))?->toISOString(),
            'nomor_referensi' => $trx->no_struk,
        ];
    }

    private function failure(string $message, int $status, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
            'meta' => (object) [],
        ], $status);
    }

    private function journal(Transaksi $trx): void
    {
        $entries = $trx->jenis_trans === Transaksi::JENIS_SETORAN
            ? [[KodeAkun::KAS, 'debit'], [KodeAkun::TABUNGAN, 'kredit']]
            : [[KodeAkun::TABUNGAN, 'debit'], [KodeAkun::KAS, 'kredit']];
        foreach ($entries as [$account, $position]) {
            DB::table('jurnal_akuntansi')->insert([
                'id_transaksi' => $trx->id,
                'kode_akun' => $account,
                'posisi' => $position,
                'jumlah' => $trx->nominal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
