<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JurnalAkuntansi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Support\ExcelExport;
use App\Support\KodeAkun;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LaporanController extends Controller
{
    public const JENIS = [
        'jurnal' => 'Jurnal Akuntansi',
        'mutasi-nasabah' => 'Mutasi Nasabah',
        'saldo-produk' => 'Saldo Per Produk',
        'laporan-tahunan' => 'Laporan Tahunan',
    ];

    public function panel(Request $request): View|RedirectResponse
    {
        $jenis = $request->string('jenis')->toString() ?: 'jurnal';

        if ($jenis === 'kartu-simpanan') {
            return redirect()->route('supervisor.laporan.kartu-simpanan');
        }

        $mulai = $request->date('mulai') ?? today()->startOfMonth();
        $sampai = $request->date('sampai') ?? today();
        $tahun = $request->integer('tahun') ?: now()->year;
        $tanggal = $request->date('tanggal') ?? today();
        $nasabahId = $request->integer('nasabah_id') ?: null;
        $produk = $request->input('produk') ?: null;

        $summary = match ($jenis) {
            'mutasi-nasabah', 'kartu-simpanan' => $this->summaryMutasi($nasabahId, $mulai, $sampai),
            'saldo-produk' => $this->summarySaldoProduk($tanggal),
            'laporan-tahunan' => $this->summaryTahunan($tahun),
            default => $this->summaryJurnal($mulai, $sampai),
        };

        $nasabahList = Nasabah::orderBy('nama_siswa')->get();
        $jenisList = self::JENIS;

        return view('supervisor.laporan-panel', compact(
            'jenis',
            'mulai',
            'sampai',
            'tahun',
            'tanggal',
            'nasabahId',
            'produk',
            'nasabahList',
            'summary',
            'jenisList'
        ));
    }

    public function jurnal(Request $request): View|Response
    {
        $mulai = $request->date('mulai') ?? today()->startOfMonth();
        $sampai = $request->date('sampai') ?? today();

        $jurnal = JurnalAkuntansi::with('transaksi.nasabah')
            ->whereHas('transaksi', fn ($q) => $q
                ->whereBetween('tanggal', [$mulai, $sampai])
                ->where('koreksi_id', null))
            ->orderBy('created_at')
            ->get();

        $totalDebit = (int) $jurnal->where('posisi', 'debit')->sum('jumlah');
        $totalKredit = (int) $jurnal->where('posisi', 'kredit')->sum('jumlah');

        if ($request->input('format') === 'excel') {
            $headers = ['Tanggal', 'No. Struk', 'Nasabah', 'Kode Akun', 'Nama Akun', 'Posisi', 'Jumlah (Rp)'];

            $rows = $jurnal->map(fn ($j) => [
                $j->created_at?->format('Y-m-d H:i'),
                $j->transaksi?->no_struk ?? '-',
                $j->transaksi?->nasabah?->nama_siswa ?? '-',
                $j->kode_akun,
                KodeAkun::label($j->kode_akun),
                $j->posisi,
                (int) $j->jumlah,
            ])->all();

            $rows[] = ['', '', '', '', 'TOTAL DEBIT', '', $totalDebit];
            $rows[] = ['', '', '', '', 'TOTAL KREDIT', '', $totalKredit];

            return ExcelExport::download(
                'jurnal-akuntansi-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d'),
                $headers,
                $rows,
                'Jurnal'
            );
        }

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.jurnal', compact('mulai', 'sampai', 'jurnal', 'totalDebit', 'totalKredit'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('jurnal-akuntansi-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d').'.pdf');
        }

        return redirect()->route('supervisor.laporan.panel', [
            'jenis' => 'jurnal',
            'mulai' => $mulai->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
        ]);
    }

    public function mutasiNasabah(Request $request): View|Response
    {
        $nasabah = Nasabah::find($request->integer('nasabah_id'));

        if (! $nasabah) {
            return redirect()->route('supervisor.laporan.panel', ['jenis' => 'mutasi-nasabah'])->with('error', 'Pilih nasabah terlebih dahulu.');
        }

        return $this->mutasiExport($request, $nasabah, 'mutasi-nasabah');
    }

    public function kartuSimpanan(Request $request): View|Response|RedirectResponse
    {
        if (! $request->filled('format')) {
            $nasabahList = Nasabah::orderBy('nama_siswa')->paginate(25);

            return view('supervisor.laporan.kartu-simpanan', compact('nasabahList'));
        }

        $nasabah = Nasabah::find($request->integer('nasabah_id'));

        if (! $nasabah) {
            return redirect()->route('supervisor.laporan.kartu-simpanan')->with('error', 'Pilih nasabah terlebih dahulu.');
        }

        return $this->mutasiExport($request, $nasabah, 'kartu-simpanan');
    }

    public function saldoProduk(Request $request): View|Response
    {
        $tanggal = $request->date('tanggal') ?? today();

        $ringkasan = Nasabah::query()
            ->select('produk')
            ->selectRaw('COUNT(*) as jumlah_nasabah')
            ->selectRaw('SUM(saldo) as total_saldo')
            ->groupBy('produk')
            ->orderBy('produk')
            ->get()
            ->map(fn ($r) => [
                'produk' => $r->produk,
                'produk_label' => $r->produk === Nasabah::PRODUK_GURU ? 'Tabungan Guru/Karyawan' : 'Tabungan Siswa',
                'jumlah_nasabah' => $r->jumlah_nasabah,
                'total_saldo' => (int) $r->total_saldo,
            ]);
        $ringkasan = $ringkasan->merge(collect([
            'jumlah_nasabah' => $ringkasan->sum('jumlah_nasabah'),
            'total_saldo' => $ringkasan->sum('total_saldo'),
        ]));

        $totalSeluruh = $ringkasan->sum('total_saldo');
        $totalNasabah = $ringkasan->sum('jumlah_nasabah');

        if ($request->input('format') === 'excel') {
            $headers = ['Produk', 'Jumlah Nasabah', 'Total Saldo (Rp)'];

            $rows = $ringkasan
                ->filter(fn ($r) => is_array($r))
                ->map(fn ($r) => [
                    $r['produk_label'],
                    (int) $r['jumlah_nasabah'],
                    (int) $r['total_saldo'],
                ])
                ->values()
                ->all();

            $rows[] = ['TOTAL', $totalNasabah, $totalSeluruh];

            return ExcelExport::download(
                'saldo-per-produk-'.$tanggal->format('Y-m-d'),
                $headers,
                $rows,
                'Saldo Produk'
            );
        }

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.saldo-produk', compact('tanggal', 'ringkasan', 'totalNasabah', 'totalSeluruh'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('saldo-per-produk-'.$tanggal->format('Y-m-d').'.pdf');
        }

        return redirect()->route('supervisor.laporan.panel', [
            'jenis' => 'saldo-produk',
            'tanggal' => $tanggal->format('Y-m-d'),
        ]);
    }

    public function tahunan(Request $request): View|Response
    {
        $tahun = $request->integer('tahun') ?: now()->year;

        $data = Transaksi::whereYear('tanggal', $tahun)
            ->where('koreksi_id', null)
            ->whereIn('jenis_trans', [Transaksi::JENIS_SETORAN, Transaksi::JENIS_PENARIKAN])
            ->selectRaw('jenis_trans, COUNT(*) as jumlah, SUM(nominal) as nominal')
            ->groupBy('jenis_trans')
            ->get()
            ->keyBy('jenis_trans');

        $totalSetoran = (int) ($data[Transaksi::JENIS_SETORAN]->nominal ?? 0);
        $totalPenarikan = (int) ($data[Transaksi::JENIS_PENARIKAN]->nominal ?? 0);
        $jumlahTransaksi = (int) $data->sum('jumlah');
        $nasabahBaru = (int) Nasabah::whereYear('created_at', $tahun)->count();
        $saldoAkhir = (int) Nasabah::sum('saldo');

        $perBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $perBulan[] = [
                'bulan' => Carbon::create()->month($bulan)->translatedFormat('F'),
                'setoran' => (int) Transaksi::whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
                    ->where('jenis_trans', Transaksi::JENIS_SETORAN)->where('koreksi_id', null)->sum('nominal'),
                'penarikan' => (int) Transaksi::whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)
                    ->where('jenis_trans', Transaksi::JENIS_PENARIKAN)->where('koreksi_id', null)->sum('nominal'),
            ];
        }

        if ($request->input('format') === 'excel') {
            $headers = ['Bulan', 'Setoran (Rp)', 'Penarikan (Rp)', 'Jumlah Transaksi'];

            $rows = $perBulan;
            foreach ($rows as $i => $row) {
                $rows[$i]['jumlah'] = (int) Transaksi::whereYear('tanggal', $tahun)
                    ->whereMonth('tanggal', $i + 1)
                    ->where('koreksi_id', null)
                    ->count();
            }

            $rows[] = ['TOTAL', $totalSetoran, $totalPenarikan, $jumlahTransaksi];

            return ExcelExport::download(
                'laporan-tahunan-'.$tahun,
                $headers,
                $rows,
                'Laporan Tahunan'
            );
        }

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.tahunan', compact('tahun', 'totalSetoran', 'totalPenarikan', 'jumlahTransaksi', 'nasabahBaru', 'saldoAkhir', 'perBulan'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('laporan-tahunan-'.$tahun.'.pdf');
        }

        return redirect()->route('supervisor.laporan.panel', ['jenis' => 'laporan-tahunan', 'tahun' => $tahun]);
    }

    protected function mutasiExport(Request $request, Nasabah $nasabah, string $jenis): View|Response
    {
        $mulai = $request->date('mulai') ?? today()->startOfMonth();
        $sampai = $request->date('sampai') ?? today();

        $mutasiRows = $nasabah->transaksi()
            ->whereBetween('tanggal', [$mulai, $sampai])
            ->where('koreksi_id', null)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldoAwal = (int) Transaksi::where('id_nasabah', $nasabah->id)
            ->where('tanggal', '<', $mulai)
            ->where('koreksi_id', null)
            ->sum(DB::raw('CASE WHEN jenis_trans = "setoran" THEN nominal ELSE -nominal END'));

        $running = $saldoAwal;
        $mutasi = $mutasiRows->map(function ($t) use (&$running) {
            $nominal = (int) $t->nominal;
            $running += $t->jenis_trans === Transaksi::JENIS_SETORAN ? $nominal : -$nominal;

            return [
                'tanggal' => $t->tanggal,
                'no_struk' => $t->no_struk,
                'jenis' => $t->jenis_label,
                'debit' => $t->jenis_trans === Transaksi::JENIS_SETORAN ? $nominal : 0,
                'kredit' => $t->jenis_trans === Transaksi::JENIS_PENARIKAN ? $nominal : 0,
                'saldo' => $running,
            ];
        });

        $saldoAkhir = $running;

        if ($request->input('format') === 'excel') {
            $headers = ['Tanggal', 'No. Struk', 'Jenis', 'Debit (Rp)', 'Kredit (Rp)', 'Saldo (Rp)', 'Teller'];

            $runningSaldo = $saldoAwal;
            $rows = $mutasiRows->map(function ($t) use (&$runningSaldo) {
                $runningSaldo += $t->jenis_trans === Transaksi::JENIS_SETORAN ? (int) $t->nominal : -(int) $t->nominal;

                return [
                    $t->tanggal?->format('Y-m-d'),
                    $t->no_struk,
                    $t->jenis_label,
                    $t->jenis_trans === Transaksi::JENIS_SETORAN ? (int) $t->nominal : 0,
                    $t->jenis_trans === Transaksi::JENIS_PENARIKAN ? (int) $t->nominal : 0,
                    $runningSaldo,
                    $t->user?->nama_petugas ?? '-',
                ];
            })->all();

            array_unshift($rows, ['TANGGAL MUTASI', $mulai->format('Y-m-d').' s.d. '.$sampai->format('Y-m-d'), '', '', '', '', '']);
            $rows[] = ['', '', 'SALDO AKHIR', '', '', $runningSaldo, ''];

            return ExcelExport::download(
                ($jenis === 'kartu-simpanan' ? 'kartu-simpanan-' : 'mutasi-').$nasabah->no_rekening,
                $headers,
                $rows,
                'Mutasi '.$nasabah->no_rekening
            );
        }

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.mutasi', compact('nasabah', 'saldoAwal', 'mutasi'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download(($jenis === 'kartu-simpanan' ? 'kartu-simpanan-' : 'mutasi-').$nasabah->no_rekening.'.pdf');
        }

        return redirect()->route('supervisor.laporan.panel', [
            'jenis' => $jenis,
            'nasabah_id' => $nasabah->id,
            'mulai' => $mulai->format('Y-m-d'),
            'sampai' => $sampai->format('Y-m-d'),
        ]);
    }

    protected function summaryJurnal($mulai, $sampai): array
    {
        $jurnal = JurnalAkuntansi::whereHas('transaksi', fn ($q) => $q
            ->whereBetween('tanggal', [$mulai, $sampai])
            ->where('koreksi_id', null))
            ->get();

        return [
            'jumlahEntri' => $jurnal->count(),
            'totalDebit' => (int) $jurnal->where('posisi', 'debit')->sum('jumlah'),
            'totalKredit' => (int) $jurnal->where('posisi', 'kredit')->sum('jumlah'),
            'seimbang' => (int) $jurnal->where('posisi', 'debit')->sum('jumlah') === (int) $jurnal->where('posisi', 'kredit')->sum('jumlah'),
        ];
    }

    protected function summaryMutasi(?int $nasabahId, $mulai, $sampai): array
    {
        $nasabah = Nasabah::find($nasabahId);

        if (! $nasabah) {
            return ['none' => true];
        }

        $saldoAwal = (int) Transaksi::where('id_nasabah', $nasabah->id)
            ->where('tanggal', '<', $mulai)
            ->where('koreksi_id', null)
            ->sum(DB::raw('CASE WHEN jenis_trans = "setoran" THEN nominal ELSE -nominal END'));

        $transaksi = $nasabah->transaksi()
            ->whereBetween('tanggal', [$mulai, $sampai])
            ->where('koreksi_id', null)
            ->get();

        $totalDebit = (int) $transaksi->where('jenis_trans', Transaksi::JENIS_SETORAN)->sum('nominal');
        $totalKredit = (int) $transaksi->where('jenis_trans', Transaksi::JENIS_PENARIKAN)->sum('nominal');

        return [
            'none' => false,
            'nama' => $nasabah->nama_siswa,
            'rekening' => $nasabah->no_rekening,
            'kelas' => $nasabah->kelas,
            'produk' => $nasabah->produk_label,
            'saldoAwal' => $saldoAwal,
            'totalDebit' => $totalDebit,
            'totalKredit' => $totalKredit,
            'saldoAkhir' => $saldoAwal + $totalDebit - $totalKredit,
        ];
    }

    protected function summarySaldoProduk($tanggal): array
    {
        $ringkasan = Nasabah::query()
            ->select('produk')
            ->selectRaw('COUNT(*) as jumlah_nasabah')
            ->selectRaw('SUM(saldo) as total_saldo')
            ->groupBy('produk')
            ->orderBy('produk')
            ->get()
            ->map(fn ($r) => [
                'produk' => $r->produk,
                'produk_label' => $r->produk === Nasabah::PRODUK_GURU ? 'Tabungan Guru/Karyawan' : 'Tabungan Siswa',
                'jumlah_nasabah' => $r->jumlah_nasabah,
                'total_saldo' => (int) $r->total_saldo,
            ]);

        return [
            'tanggal' => $tanggal,
            'ringkasan' => $ringkasan,
            'totalNasabah' => (int) $ringkasan->sum('jumlah_nasabah'),
            'totalSeluruh' => (int) $ringkasan->sum('total_saldo'),
        ];
    }

    protected function summaryTahunan(int $tahun): array
    {
        $data = Transaksi::whereYear('tanggal', $tahun)
            ->where('koreksi_id', null)
            ->whereIn('jenis_trans', [Transaksi::JENIS_SETORAN, Transaksi::JENIS_PENARIKAN])
            ->selectRaw('jenis_trans, COUNT(*) as jumlah, SUM(nominal) as nominal')
            ->groupBy('jenis_trans')
            ->get()
            ->keyBy('jenis_trans');

        return [
            'tahun' => $tahun,
            'totalSetoran' => (int) ($data[Transaksi::JENIS_SETORAN]->nominal ?? 0),
            'totalPenarikan' => (int) ($data[Transaksi::JENIS_PENARIKAN]->nominal ?? 0),
            'jumlahTransaksi' => (int) $data->sum('jumlah'),
            'nasabahBaru' => (int) Nasabah::whereYear('created_at', $tahun)->count(),
            'saldoAkhir' => (int) Nasabah::sum('saldo'),
        ];
    }
}
