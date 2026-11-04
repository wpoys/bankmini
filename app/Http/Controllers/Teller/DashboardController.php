<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\JadwalPetugas;
use App\Models\LaporanHarian;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalSetoranHariIni = (int) Transaksi::whereDate('tanggal', today())
            ->where('id_user', Auth::id())
            ->where('jenis_trans', 'setoran')
            ->sum('nominal');

        $totalPenarikanHariIni = (int) Transaksi::whereDate('tanggal', today())
            ->where('id_user', Auth::id())
            ->where('jenis_trans', 'penarikan')
            ->sum('nominal');

        $jumlahTransaksi = Transaksi::whereDate('tanggal', today())
            ->where('id_user', Auth::id())
            ->count();

        $laporanHariIni = LaporanHarian::where('teller_id', Auth::id())
            ->whereDate('tanggal', today())
            ->first();

        $saldoKas = $laporanHariIni
            ? $laporanHariIni->saldo_akhir_sistem
            : $totalSetoranHariIni - $totalPenarikanHariIni;

        $transaksiTerbaru = Transaksi::with(['nasabah', 'user'])
            ->where('id_user', Auth::id())
            ->latest()
            ->take(8)
            ->get();

        $jadwalHariIni = JadwalPetugas::whereDate('tanggal', today())
            ->where('user_id', Auth::id())
            ->orderBy('jam_mulai')
            ->get();

        return view('dashboard.teller', compact(
            'totalSetoranHariIni',
            'totalPenarikanHariIni',
            'jumlahTransaksi',
            'saldoKas',
            'transaksiTerbaru',
            'laporanHariIni',
            'jadwalHariIni',
        ));
    }
}
