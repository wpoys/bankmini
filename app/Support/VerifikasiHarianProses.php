<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Transaksi;
use App\Models\User;
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
    /**
     * Verifikasi satu tanggal. Idempoten: tanggal yang sudah diverifikasi
     * tidak dapat diverifikasi ulang.
     */
    public static function verifikasi(string $tanggal, User $aktor, ?string $catatan = null): VerifikasiHarian
    {
        $hari = Carbon::parse($tanggal)->startOfDay();

        return DB::transaction(function () use ($hari, $aktor, $catatan) {
            // Kunci baris hari tersebut agar tidak ada dua proses bersamaan.
            $locks = VerifikasiHarian::whereDate('tanggal', $hari)
                ->lockForUpdate()
                ->get();

            $sudah = $locks->first();

            if ($sudah && $sudah->verifikasi_at !== null) {
                throw new \RuntimeException('Verifikasi tanggal '.$hari->format('d/m/Y').' sudah selesai dan tidak dapat diulang.');
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

            if ($catatan) {
                ActivityLog::catat(
                    $aktor->id,
                    'verifikasi_harian',
                    'Verifikasi transaksi tanggal '.$hari->format('d/m/Y').' ('.$ringkasan['jumlah'].' transaksi) oleh '.$aktor->nama_petugas.'.'
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
            ->where('status', '!=', Transaksi::STATUS_DITOLAK)
            ->orderBy('created_at')
            ->orderBy('id');

        if ($verifikasi && $verifikasi->verifikasi_at) {
            // Sudah diverifikasi: tidak ada transaksi "belum diverifikasi".
            $query->whereRaw('1 = 0');
        }

        return $query->get();
    }
}
