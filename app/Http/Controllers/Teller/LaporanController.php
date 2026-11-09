<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DetailLaporan;
use App\Models\Konfigurasi;
use App\Models\LaporanHarian;
use App\Models\MutasiBrankas;
use App\Models\RekonsiliasiKas;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function index(): View
    {
        $laporan = LaporanHarian::with('teller')
            ->where('teller_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('teller.laporan.index', compact('laporan'));
    }

    public function create(): View
    {
        $transaksi = Transaksi::with('nasabah')
            ->where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_APPROVED)
            ->whereDate('tanggal', today())
            ->orderBy('created_at')
            ->get();

        $laporanHariIni = LaporanHarian::where('teller_id', Auth::id())
            ->whereDate('tanggal', today())
            ->first();

        $totalSetoran = (int) $transaksi->where('jenis_trans', 'setoran')->sum('nominal');
        $totalPenarikan = (int) $transaksi->where('jenis_trans', 'penarikan')->sum('nominal');

        $saldoAwalKas = $this->hitungSaldoAwalKas();
        $saldoAkhirSistem = $saldoAwalKas + $totalSetoran - $totalPenarikan;

        $referensiKasAwalHarian = Konfigurasi::kasAwalHarian();

        return view('teller.laporan.create', compact(
            'transaksi',
            'laporanHariIni',
            'referensiKasAwalHarian',
            'totalSetoran',
            'totalPenarikan',
            'saldoAwalKas',
            'saldoAkhirSistem',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $denominasi = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100];

        $validated = $request->validate([
            'saldo_akhir_fisik' => ['required', 'integer', 'min:0'],
            'kas_dipindahkan' => ['nullable', 'integer', 'min:0'],
            'pecahan' => ['nullable', 'array:'.implode(',', $denominasi)],
            'pecahan.*' => ['integer', 'min:0', 'max:100000'],
        ]);

        $existing = LaporanHarian::where('teller_id', Auth::id())
            ->whereDate('tanggal', today())
            ->first();

        if ($existing && ! in_array($existing->status, ['draft', 'menunggu_approval', 'pending'])) {
            return back()->with('error', 'Laporan hari ini sudah disetujui/ditolak dan tidak dapat diubah.');
        }

        $totalSetoran = (int) Transaksi::where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_APPROVED)
            ->whereDate('tanggal', today())
            ->where('jenis_trans', 'setoran')
            ->sum('nominal');

        $totalPenarikan = (int) Transaksi::where('id_user', Auth::id())
            ->where('status', Transaksi::STATUS_APPROVED)
            ->whereDate('tanggal', today())
            ->where('jenis_trans', 'penarikan')
            ->sum('nominal');

        $saldoAwalKas = $this->hitungSaldoAwalKas();
        $saldoAkhirSistem = $saldoAwalKas + $totalSetoran - $totalPenarikan;

        $totalFisik = (int) $validated['saldo_akhir_fisik'];
        $selisih = $totalFisik - $saldoAkhirSistem;

        $kasDipindahkan = ! is_null($request->input('kas_dipindahkan')) && $request->input('kas_dipindahkan') !== ''
            ? (int) $request->input('kas_dipindahkan')
            : null;

        if ($kasDipindahkan !== null && $kasDipindahkan > $totalFisik) {
            return back()->withInput()->with('error', 'Jumlah kas yang dipindahkan ke brankas tidak boleh melebihi saldo fisik kas (Rp '.number_format($totalFisik, 0, ',', '.').').');
        }

        try {
            $laporan = DB::transaction(function () use ($existing, $validated, $saldoAwalKas, $totalSetoran, $totalPenarikan, $saldoAkhirSistem, $totalFisik, $selisih, $kasDipindahkan) {
                $data = [
                    'teller_id' => Auth::id(),
                    'tanggal' => today(),
                    'saldo_awal_kas' => $saldoAwalKas,
                    'total_setoran' => $totalSetoran,
                    'total_penarikan' => $totalPenarikan,
                    'saldo_akhir_sistem' => $saldoAkhirSistem,
                    'saldo_akhir_fisik' => $totalFisik,
                    'selisih' => $selisih,
                    'status' => 'draft',
                    'kas_dipindahkan' => $kasDipindahkan,
                ];

                $laporan = $existing
                    ? tap($existing)->update($data)
                    : LaporanHarian::create($data);

                $transaksiHariIni = Transaksi::where('id_user', Auth::id())
                    ->whereDate('tanggal', today())
                    ->where('status', Transaksi::STATUS_APPROVED)
                    ->pluck('id');

                if ($transaksiHariIni->isNotEmpty()) {
                    DetailLaporan::where('laporan_id', $laporan->id)
                        ->whereIn('transaksi_id', $transaksiHariIni)
                        ->delete();

                    foreach ($transaksiHariIni as $idTransaksi) {
                        DetailLaporan::firstOrCreate([
                            'laporan_id' => $laporan->id,
                            'transaksi_id' => $idTransaksi,
                        ]);
                    }
                }

                $this->simpanPecahan($laporan->id, $validated['pecahan'] ?? null, $totalFisik, $selisih);

                return $laporan;
            });

            ActivityLog::catat(Auth::id(), 'buat_laporan', 'Laporan harian dibuat/diupdate untuk tanggal '.$laporan->tanggal.'.');

            if ($request->boolean('submit_after_save')) {
                return $this->submit($laporan);
            }

            $message = 'Laporan harian berhasil disimpan. ';
            $message .= $selisih === 0
                ? 'Kas fisik selaras dengan sistem.'
                : 'PERHATIAN: terdapat selisih kas sebesar Rp '.number_format(abs($selisih), 0, ',', '.').'. Laporan tetap dapat dikirim; Supervisor akan menahan (pending) laporan hingga selisih diselesaikan.';

            if ($selisih !== 0) {
                UserNotification::kirim(
                    Auth::id(),
                    UserNotification::TIPE_WARNING,
                    'Selisih Kas Terdeteksi',
                    'Terdapat selisih kas sebesar Rp '.number_format(abs($selisih), 0, ',', '.').'. Periksa kembali uang fisik Anda; laporan akan ditahan (pending) bila tetap dikirim dengan selisih.',
                    route('teller.laporan.create'),
                );
            }

            return redirect()->route('teller.laporan.show', $laporan->id)
                ->with('success', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan laporan: '.$e->getMessage());
        }
    }

    public function show(LaporanHarian $laporan): View
    {
        if ($laporan->teller_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $laporan->load(['teller', 'detailLaporan.transaksi.nasabah', 'detailLaporan.transaksi.user', 'approver', 'rekonsiliasi.detail']);

        return view('teller.laporan.show', compact('laporan'));
    }

    public function pdf(LaporanHarian $laporan): Response
    {
        if ($laporan->teller_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        $laporan->load(['teller', 'detailLaporan.transaksi.nasabah', 'detailLaporan.transaksi.user', 'approver', 'rekonsiliasi.detail']);

        $pdf = Pdf::loadView('teller.laporan.pdf.harian', compact('laporan'));
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('laporan-harian-teller-'.$laporan->tanggal->format('Y-m-d').'.pdf');
    }

    public function submit(LaporanHarian $laporan): RedirectResponse
    {
        if ($laporan->teller_id !== Auth::id()) {
            abort(403);
        }

        if (! in_array($laporan->status, ['draft', 'pending'])) {
            return back()->with('error', 'Hanya laporan berstatus draft atau pending yang dapat dikirim.');
        }

        $laporan->update([
            'status' => 'menunggu_approval',
            'submitted_at' => now(),
        ]);

        ActivityLog::catat(Auth::id(), 'submit_laporan', "Laporan harian tanggal {$laporan->tanggal} dikirim untuk verifikasi.".($laporan->selisih ? ' (terdapat selisih kas Rp '.number_format(abs($laporan->selisih), 0, ',', '.').')' : ''));

        $supervisor = User::where('role', User::ROLE_SUPERVISOR)
            ->where('status', 'active')
            ->get();

        foreach ($supervisor as $spv) {
            UserNotification::kirim(
                $spv,
                UserNotification::TIPE_INFO,
                'Laporan Menunggu Verifikasi',
                "Laporan harian tanggal {$laporan->tanggal} dari ".auth()->user()->nama_petugas.' menunggu verifikasi Anda.',
                route('supervisor.verifikasi.show', $laporan->id),
            );
        }

        return redirect()->route('teller.laporan.show', $laporan->id)
            ->with('success', 'Laporan berhasil dikirim ke Supervisor untuk verifikasi.');
    }

    /**
     * Simpan rincian pecahan uang fisik ke tabel rekonsiliasi_kas.
     * Data lama diganti total agar selalu mencerminkan isian terbaru.
     */
    protected function simpanPecahan(int $laporanId, ?array $pecahan, int $totalFisik, int $selisih): void
    {
        $rekonsiliasi = RekonsiliasiKas::where('laporan_id', $laporanId)->first();
        $isiPecahan = array_filter((array) $pecahan, fn ($jml) => (int) $jml > 0);

        if ($isiPecahan === []) {
            $rekonsiliasi?->delete();

            return;
        }

        $rekonsiliasi = RekonsiliasiKas::updateOrCreate(
            ['laporan_id' => $laporanId],
            [
                'teller_id' => Auth::id(),
                'total_fisik' => $totalFisik,
                'selisih' => $selisih,
                'status' => $selisih === 0 ? 'cocok' : 'selisih',
            ],
        );

        $rekonsiliasi->detail()->delete();

        foreach ($isiPecahan as $pecahanNominal => $jumlahLembar) {
            $rekonsiliasi->detail()->create([
                'pecahan' => (int) $pecahanNominal,
                'jumlah_lembar' => (int) $jumlahLembar,
                'subtotal' => (int) $pecahanNominal * (int) $jumlahLembar,
            ]);
        }

        ActivityLog::catat(Auth::id(), 'rekonsiliasi_kas', 'Rincian pecahan kas fisik laporan diperbarui ('.count($isiPecahan).').');
    }

    protected function hitungSaldoAwalKas(): int
    {
        $kasDiambilDariBrankas = (int) MutasiBrankas::where('user_id', Auth::id())
            ->where('jenis', 'kas_awal_teller')
            ->whereDate('created_at', today())
            ->sum(DB::raw('ABS(nominal)'));

        $laporanKemarin = LaporanHarian::where('teller_id', Auth::id())
            ->whereDate('tanggal', '<', today())
            ->where('status', 'disetujui')
            ->latest('tanggal')
            ->first();

        $saldoKasKemarin = $laporanKemarin
            ? max(0, (int) $laporanKemarin->saldo_akhir_sistem - (int) $laporanKemarin->kas_dipindahkan)
            : 0;

        return $saldoKasKemarin + $kasDiambilDariBrankas;
    }
}
