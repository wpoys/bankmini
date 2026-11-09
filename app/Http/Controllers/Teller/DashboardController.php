<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\JadwalPetugas;
use App\Models\LaporanHarian;
use App\Models\MutasiBrankas;
use App\Models\Transaksi;
use App\Support\BrankasService;
use App\Support\QrisNasabah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $saldoBrankas = BrankasService::saldo();
        $totalSetoranHariIni = (int) Transaksi::whereDate('tanggal', today())
            ->where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_APPROVED)
            ->where('jenis_trans', 'setoran')
            ->sum('nominal');

        $totalPenarikanHariIni = (int) Transaksi::whereDate('tanggal', today())
            ->where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_APPROVED)
            ->where('jenis_trans', 'penarikan')
            ->sum('nominal');

        $jumlahTransaksi = Transaksi::whereDate('tanggal', today())
            ->where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_APPROVED)
            ->count();

        $laporanHariIni = LaporanHarian::where('teller_id', Auth::id())
            ->whereDate('tanggal', today())
            ->first();

        $kasAwalHariIni = (int) MutasiBrankas::where('user_id', Auth::id())
            ->where('jenis', 'kas_awal_teller')
            ->whereDate('created_at', today())
            ->sum(DB::raw('ABS(nominal)'));

        $saldoKas = $laporanHariIni
            ? $laporanHariIni->saldo_akhir_sistem
            : $kasAwalHariIni + $totalSetoranHariIni - $totalPenarikanHariIni;

        $transaksiTerbaru = Transaksi::with(['nasabah', 'user'])
            ->where('id_user', Auth::id())
            ->latest()
            ->take(8)
            ->get();

        $transaksiPending = Transaksi::where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_PENDING)
            ->count();

        $grafikJam = [];
        for ($jam = 0; $jam <= 23; $jam++) {
            $mulai = sprintf('%02d:00:00', $jam);
            $akhir = sprintf('%02d:59:59', $jam);
            $grafikJam[] = [
                'label' => sprintf('%02d:00', $jam),
                'setoran' => (int) Transaksi::whereDate('tanggal', today())
                    ->where('id_user', Auth::id())
                    ->where('status', Transaksi::STATUS_APPROVED)
                    ->where('jenis_trans', 'setoran')
                    ->whereTime('created_at', '>=', $mulai)
                    ->whereTime('created_at', '<=', $akhir)
                    ->sum('nominal'),
                'penarikan' => (int) Transaksi::whereDate('tanggal', today())
                    ->where('id_user', Auth::id())
                    ->where('status', Transaksi::STATUS_APPROVED)
                    ->where('jenis_trans', 'penarikan')
                    ->whereTime('created_at', '>=', $mulai)
                    ->whereTime('created_at', '<=', $akhir)
                    ->sum('nominal'),
            ];
        }

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
            'transaksiPending',
            'grafikJam',
            'laporanHariIni',
            'jadwalHariIni',
            'saldoBrankas',
        ));
    }

    public function ambilKas(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'jumlah' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], $request->user()->password)) {
            throw ValidationException::withMessages(['password' => 'Password teller tidak sesuai.']);
        }

        BrankasService::ambilKas((int) $validated['jumlah'], (int) $request->user()->id);

        return redirect()->route('teller.dashboard')->with('success', 'Kas berhasil diambil dari brankas dan langsung ditambahkan ke kas awal teller.');
    }

    public function cariNasabah(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cari' => ['required', 'string', 'max:100'],
        ]);

        $nasabah = QrisNasabah::cari($validated['cari']);

        if (! $nasabah || $nasabah->isArsip()) {
            return response()->json([
                'success' => false,
                'message' => 'Nasabah aktif tidak ditemukan. Periksa nomor rekening, NIS, atau QR.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'nasabah' => [
                'id' => $nasabah->id,
                'nama' => $nasabah->nama_siswa,
                'rekening' => $nasabah->no_rekening,
                'nis' => $nasabah->nis,
                'kelas' => $nasabah->kelas,
                'saldo' => (int) $nasabah->saldo,
                'saldo_formatted' => $nasabah->saldo_rupiah,
            ],
            'form_url' => route('teller.transaksi.form', $nasabah),
        ]);
    }
}
