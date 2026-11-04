<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalPetugas;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\PermintaanResetPin;
use App\Models\Transaksi;use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalNasabah = Nasabah::count();
        $totalUser = User::count();
        $transaksiHariIni = Transaksi::whereDate('tanggal', today())->count();
        $laporanPending = LaporanHarian::where('status', 'menunggu_approval')->count();
        $nasabahBaruHariIni = Nasabah::whereDate('created_at', today())->count();

        $permintaanReset = PermintaanResetPin::with('nasabah')
            ->orderByRaw('CASE WHEN status = "pending" THEN 0 ELSE 1 END')
            ->latest()
            ->take(5)
            ->get();
        $permintaanResetPending = (int) PermintaanResetPin::where('status', PermintaanResetPin::STATUS_PENDING)->count();

        $userTerbaru = User::latest()->take(5)->get();

        $jadwalPetugasHariIni = (int) JadwalPetugas::whereDate('tanggal', today())->count();

        $grafikTransaksi = $this->grafikTransaksiMingguan();

        return view('dashboard.admin', compact(
            'totalNasabah',
            'totalUser',
            'transaksiHariIni',
            'laporanPending',
            'nasabahBaruHariIni',
            'permintaanReset',
            'permintaanResetPending',
            'userTerbaru',
            'grafikTransaksi',
            'jadwalPetugasHariIni',
        ));
    }

    protected function grafikTransaksiMingguan(): array
    {
        $labels = [];
        $setoran = [];
        $penarikan = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $labels[] = $date->format('d/m');

            $setoran[] = (int) Transaksi::whereDate('tanggal', $date)
                ->where('jenis_trans', 'setoran')
                ->sum('nominal');

            $penarikan[] = (int) Transaksi::whereDate('tanggal', $date)
                ->where('jenis_trans', 'penarikan')
                ->sum('nominal');
        }

        return compact('labels', 'setoran', 'penarikan');
    }
}
