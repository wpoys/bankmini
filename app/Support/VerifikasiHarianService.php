<?php

namespace App\Support;

use App\Models\LaporanHarian;
use App\Models\Transaksi;
use App\Models\VerifikasiHarian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agregasi transaksi per tanggal untuk verifikasi supervisor.
 *
 * Transaksi dikelompokkan berdasarkan tanggal (bukan per transaksi), sehingga
 * supervisor cukup memverifikasi satu kali per hari.
 */
class VerifikasiHarianService
{
    /**
     * Daftar tanggal yang punya transaksi, dikelompokkan per tanggal.
     *
     * @return Collection<int, array{
     *     tanggal: string, ringkasan: array, sudah: ?VerifikasiHarian
     * }>
     */
    public static function daftarTanggal(int $limit = 30): Collection
    {
        $tanggalTerdaftar = Transaksi::query()
            ->selectRaw('DATE(tanggal) as tgl, COUNT(*) as jml')
            ->whereNotNull('tanggal')
            ->where('status', Transaksi::STATUS_APPROVED)
            ->groupBy('tgl')
            ->orderByDesc('tgl')
            ->limit($limit)
            ->pluck('jml', 'tgl');

        $sudahDiverifikasi = VerifikasiHarian::all()->keyBy(fn ($v) => $v->tanggal->toDateString());

        return $tanggalTerdaftar->map(fn ($jumlah, $tgl) => [
            'tanggal' => $tgl,
            'ringkasan' => self::ringkasan(Carbon::parse($tgl)),
            'sudah' => $sudahDiverifikasi->get($tgl),
        ])->values();
    }

    /**
     * Ringkasan transaksi satu tanggal.
     *
     * @return array{jumlah:int,total_setoran:int,total_penarikan:int,total_kas:int,selisih:?int}
     */
    public static function ringkasan(Carbon $tanggal): array
    {
        $row = Transaksi::query()
            ->whereDate('tanggal', $tanggal)
            ->where('status', Transaksi::STATUS_APPROVED)
            ->selectRaw('
                COUNT(*) as jumlah,
                SUM(CASE WHEN jenis_trans IN ("setoran","koreksi_setoran") THEN nominal ELSE 0 END) as total_setoran,
                SUM(CASE WHEN jenis_trans IN ("penarikan","koreksi_penarikan") THEN nominal ELSE 0 END) as total_penarikan
            ')
            ->first();

        $setoran = (int) ($row->total_setoran ?? 0);
        $penarikan = (int) ($row->total_penarikan ?? 0);

        return [
            'jumlah' => (int) ($row->jumlah ?? 0),
            'total_setoran' => $setoran,
            'total_penarikan' => $penarikan,
            'total_kas' => $setoran - $penarikan,
            'selisih' => self::selisihLaporan($tanggal),
        ];
    }

    /**
     * Selisih kas dari laporan kas harian teller (bila sudah ada).
     */
    public static function selisihLaporan(Carbon $tanggal): ?int
    {
        $selisih = LaporanHarian::whereDate('tanggal', $tanggal)
            ->whereNotNull('selisih')
            ->orderByDesc('id')
            ->value('selisih');

        return $selisih === null ? null : (int) $selisih;
    }

    /**
     * Transaksi satu tanggal, urut waktu.
     */
    public static function transaksiTanggal(Carbon $tanggal)
    {
        return Transaksi::with(['nasabah', 'user'])
            ->whereDate('tanggal', $tanggal)
            ->where('status', Transaksi::STATUS_APPROVED)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
