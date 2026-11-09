<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\KoreksiTransaksi;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\Transaksi;
use App\Support\BrankasService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $saldoBrankas = BrankasService::saldo();
        $laporanPending = LaporanHarian::where('status', 'menunggu_approval')->count();
        $laporanDisetujui = LaporanHarian::where('status', 'disetujui')->count();
        $laporanDitolak = LaporanHarian::where('status', 'ditolak')->count();
        $permintaanPinPending = PermintaanResetPin::where('status', PermintaanResetPin::STATUS_PENDING)->count();
        $koreksiPending = KoreksiTransaksi::where('status', 'menunggu_approval')->count();
        $nasabahAktif = Nasabah::where('arsip', false)->count();
        $ringkasanKasBulanIni = Transaksi::where('status', Transaksi::STATUS_APPROVED)
            ->whereBetween('tanggal', [today()->startOfMonth(), today()])
            ->selectRaw(
                'SUM(CASE WHEN jenis_trans IN (?, ?) THEN nominal ELSE 0 END) as total_setoran, SUM(CASE WHEN jenis_trans IN (?, ?) THEN nominal ELSE 0 END) as total_penarikan',
                [
                    Transaksi::JENIS_SETORAN,
                    Transaksi::JENIS_KOREKSI_SETORAN,
                    Transaksi::JENIS_PENARIKAN,
                    Transaksi::JENIS_KOREKSI_PENARIKAN,
                ],
            )
            ->first();
        $totalSetoranBulanIni = (int) ($ringkasanKasBulanIni->total_setoran ?? 0);
        $totalPenarikanBulanIni = (int) ($ringkasanKasBulanIni->total_penarikan ?? 0);
        $daftarPermintaanPin = PermintaanResetPin::with('nasabah')
            ->orderByRaw('CASE WHEN status = "pending" THEN 0 ELSE 1 END')
            ->latest()
            ->take(5)
            ->get();

        $daftarMenunggu = LaporanHarian::with('teller')
            ->where('status', 'menunggu_approval')
            ->latest()
            ->take(5)
            ->get();

        $grafik = [
            'labels' => ['Draft', 'Menunggu', 'Disetujui', 'Ditolak'],
            'data' => [
                LaporanHarian::where('status', 'draft')->count(),
                $laporanPending,
                $laporanDisetujui,
                $laporanDitolak,
            ],
        ];

        return view('dashboard.supervisor', compact(
            'laporanPending',
            'laporanDisetujui',
            'laporanDitolak',
            'permintaanPinPending',
            'koreksiPending',
            'nasabahAktif',
            'totalSetoranBulanIni',
            'totalPenarikanBulanIni',
            'daftarPermintaanPin',
            'daftarMenunggu',
            'grafik',
            'saldoBrankas',
        ));
    }
}
