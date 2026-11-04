<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\JadwalPetugas;
use App\Models\LaporanHarian;
use App\Models\PermintaanResetPin;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $laporanPending = LaporanHarian::where('status', 'menunggu_approval')->count();
        $laporanDisetujui = LaporanHarian::where('status', 'disetujui')->count();
        $laporanDitolak = LaporanHarian::where('status', 'ditolak')->count();
        $permintaanPinPending = PermintaanResetPin::where('status', PermintaanResetPin::STATUS_PENDING)->count();
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

        $history = LaporanHarian::with('teller', 'approver')
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->latest()
            ->take(8)
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

        $jadwalHariIni = JadwalPetugas::with('user')
            ->whereDate('tanggal', today())
            ->orderBy('jam_mulai')
            ->get();

        return view('dashboard.supervisor', compact(
            'laporanPending',
            'laporanDisetujui',
            'laporanDitolak',
            'permintaanPinPending',
            'daftarPermintaanPin',
            'daftarMenunggu',
            'history',
            'grafik',
            'jadwalHariIni',
        ));
    }
}
