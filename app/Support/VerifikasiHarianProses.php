<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VerifikasiHarian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Proses verifikasi transaksi supervisor per hari.
 *
 * Aturan:
 * - Transaksi dikelompokkan berdasarkan tanggal.
 * - Satu tanggal hanya dapat diverifikasi satu kali.
 * - Jika Admin dan Supervisor membuka tanggal yang sama bersamaan,
 *   hanya satu yang berhasil (row lock + pengecekan status di dalam transaksi).
 */
class VerifikasiHarianProses
{
    public static function verifikasi(
        string $tanggal,
        User $aktor,
        ?string $catatan = null,
        string $keputusan = VerifikasiHarian::STATUS_DITERIMA,
    ): VerifikasiHarian {
        $hari = Carbon::parse($tanggal)->startOfDay();

        if (! in_array($keputusan, [VerifikasiHarian::STATUS_DITERIMA, VerifikasiHarian::STATUS_DITOLAK], true)) {
            throw new \RuntimeException('Keputusan verifikasi tidak valid.');
        }

        if ($keputusan === VerifikasiHarian::STATUS_DITOLAK && blank($catatan)) {
            throw new \RuntimeException('Catatan wajib diisi saat menolak verifikasi.');
        }

        return DB::transaction(function () use ($hari, $aktor, $catatan, $keputusan) {
            $sudah = VerifikasiHarian::whereDate('tanggal', $hari)
                ->lockForUpdate()
                ->first();

            if ($sudah?->sudah_diverifikasi) {
                throw new \RuntimeException('Verifikasi tanggal '.$hari->format('d/m/Y').' sudah diterima dan tidak dapat diulang.');
            }

            $ringkasan = VerifikasiHarianService::ringkasan($hari);

            if ($ringkasan['jumlah'] === 0) {
                throw new \RuntimeException('Tidak ada transaksi pada tanggal '.$hari->format('d/m/Y').'.');
            }

            $payload = [
                'tanggal' => $hari->toDateString(),
                'jumlah_transaksi' => $ringkasan['jumlah'],
                'total_setoran' => $ringkasan['total_setoran'],
                'total_penarikan' => $ringkasan['total_penarikan'],
                'total_kas' => $ringkasan['total_kas'],
                'selisih' => $ringkasan['selisih'],
                'status' => $keputusan,
                'catatan' => $keputusan === VerifikasiHarian::STATUS_DITOLAK ? trim((string) $catatan) : $sudah?->catatan,
                'transaksi_tercatat' => $ringkasan['jumlah'],
                'verifikasi_oleh' => $aktor->id,
                'verifikasi_at' => now(),
            ];

            if ($sudah) {
                $sudah->update($payload);
                $verifikasi = $sudah;
            } else {
                $verifikasi = VerifikasiHarian::create($payload);
            }

            $ditolak = $keputusan === VerifikasiHarian::STATUS_DITOLAK;
            ActivityLog::catat(
                $aktor->id,
                'verifikasi_harian',
                ($ditolak ? 'Menolak' : 'Menerima').' verifikasi transaksi tanggal '.$hari->format('d/m/Y').' ('.$ringkasan['jumlah'].' transaksi) oleh '.$aktor->nama_petugas.'.'.($ditolak ? ' Catatan: '.trim((string) $catatan) : '')
            );

            $tellerIds = Transaksi::whereDate('tanggal', $hari)
                ->where('status', Transaksi::STATUS_APPROVED)
                ->whereNotNull('id_user')
                ->distinct()
                ->pluck('id_user');

            foreach ($tellerIds as $tellerId) {
                UserNotification::kirim(
                    (int) $tellerId,
                    $ditolak ? UserNotification::TIPE_DANGER : UserNotification::TIPE_SUCCESS,
                    $ditolak ? 'Verifikasi transaksi ditolak' : 'Verifikasi transaksi diterima',
                    $ditolak
                        ? 'Transaksi tanggal '.$hari->format('d/m/Y').' ditolak. Catatan supervisor: '.trim((string) $catatan)
                        : 'Transaksi tanggal '.$hari->format('d/m/Y').' telah diterima supervisor.',
                    route('teller.koreksi.index'),
                );
            }

            return $verifikasi;
        });
    }

    /**
     * Transaksi yang belum diverifikasi pada tanggal tertentu.
     */
    public static function transaksiBelumDiverifikasi(Carbon $tanggal)
    {
        $verifikasi = VerifikasiHarian::whereDate('tanggal', $tanggal)->first();

        $query = Transaksi::with(['nasabah', 'user'])
            ->whereDate('tanggal', $tanggal)
            ->where('status', Transaksi::STATUS_APPROVED)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($verifikasi?->sudah_diverifikasi) {
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }
}
