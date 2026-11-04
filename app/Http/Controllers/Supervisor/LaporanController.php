<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\LaporanHarian;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Support\ExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function dashboard(): View
    {
        $totalSetoranBulanIni = (int) Transaksi::where('jenis_trans', 'setoran')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('nominal');

        $totalPenarikanBulanIni = (int) Transaksi::where('jenis_trans', 'penarikan')
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->sum('nominal');

        $jumlahTransaksi = (int) Transaksi::count();
        $totalSimpanan = (int) Nasabah::sum('saldo');
        $jumlahNasabah = (int) Nasabah::count();

        $transaksiBulan = $this->grafikTransaksiBulanan();

        return view('supervisor.laporan.dashboard', compact(
            'totalSetoranBulanIni',
            'totalPenarikanBulanIni',
            'jumlahTransaksi',
            'totalSimpanan',
            'jumlahNasabah',
            'transaksiBulan',
        ));
    }

    public function laporanHarian(Request $request): View|Response|RedirectResponse
    {
        $mulai = $request->date('mulai') ?? today()->startOfMonth();
        $sampai = $request->date('sampai') ?? today();

        $tellerTerpilih = $request->integer('teller') > 0
            ? User::where('role', User::ROLE_TELLER)->find($request->integer('teller'))
            : null;
        $tellerId = $tellerTerpilih?->id;
        $tellerLabel = $tellerTerpilih?->nama_petugas;

        $query = LaporanHarian::with(['teller', 'rekonsiliasi.detail'])
            ->whereBetween('tanggal', [$mulai, $sampai])
            ->when($tellerId, fn ($q) => $q->where('teller_id', $tellerId))
            ->orderBy('tanggal')
            ->orderBy('id');

        $laporan = $query->get();

        $nasabahId = $request->integer('nasabah_id');
        $nasabah = $nasabahId > 0 ? Nasabah::find($nasabahId) : null;

        $bukuBesar = $this->dataBukuBesar($mulai, $sampai, $nasabah, $tellerId);
        $saldoAwal = $bukuBesar['saldoAwal'];
        $mutasi = $bukuBesar['mutasi'];

        $totalTransaksi = Transaksi::whereBetween('tanggal', [$mulai, $sampai])
            ->whereNull('koreksi_id')
            ->when($tellerId, fn ($q) => $q->where('id_user', $tellerId))
            ->count();

        $totalSetoran = (int) $laporan->sum('total_setoran');
        $totalPenarikan = (int) $laporan->sum('total_penarikan');

        $jumlahHari = $laporan
            ->pluck('tanggal')
            ->map(fn ($tanggal) => $tanggal->format('Y-m-d'))
            ->unique()
            ->count();

        $rekapStatus = [
            'disetujui' => $laporan->where('status', 'disetujui')->count(),
            'menunggu_approval' => $laporan->where('status', 'menunggu_approval')->count(),
            'pending' => $laporan->where('status', 'pending')->count(),
            'ditolak' => $laporan->where('status', 'ditolak')->count(),
            'draft' => $laporan->where('status', 'draft')->count(),
        ];

        $saldoAwalPeriode = (int) $laporan
            ->groupBy('teller_id')
            ->sum(fn (Collection $laporanTeller) => (int) $laporanTeller->first()->saldo_awal_kas);

        $saldoAkhirPeriode = $saldoAwalPeriode + $totalSetoran - $totalPenarikan;
        $totalSelisih = (int) $laporan->sum('selisih');

        $laporanDenganRincianFisik = $laporan->filter(
            fn (LaporanHarian $l) => $l->rekonsiliasi && $l->rekonsiliasi->detail->isNotEmpty()
        );
        $laporanFisikTanpaRincian = $laporan->filter(
            fn (LaporanHarian $l) => $l->saldo_akhir_fisik !== null
                && (! $l->rekonsiliasi || $l->rekonsiliasi->detail->isEmpty())
        );
        $laporanFisikBelumDiisi = $laporan->filter(fn (LaporanHarian $l) => $l->saldo_akhir_fisik === null);

        $detailSubtitle = 'Periode '.$mulai->format('d/m/Y').' s.d. '.$sampai->format('d/m/Y');

        if ($tellerLabel) {
            $detailSubtitle .= ' | Teller: '.$tellerLabel;
        }

        if ($nasabah) {
            $detailSubtitle .= ' | Nasabah: '.$nasabah->nama_siswa.' ('.$nasabah->no_rekening.')';
        }

        if ($request->input('format') === 'excel') {
            $headers = ['Tanggal', 'Teller', 'Saldo Awal Kas', 'Total Setoran', 'Total Penarikan', 'Saldo Akhir Sistem', 'Saldo Akhir Fisik', 'Selisih', 'Status', 'Kas Dipindahkan'];

            $rows = $laporan->map(fn ($l) => [
                $l->tanggal?->format('Y-m-d'),
                $l->teller?->nama_petugas ?? '-',
                (int) $l->saldo_awal_kas,
                (int) $l->total_setoran,
                (int) $l->total_penarikan,
                (int) $l->saldo_akhir_sistem,
                (int) $l->saldo_akhir_fisik,
                (int) $l->selisih,
                $l->status_label ?? $l->status,
                (int) ($l->kas_dipindahkan ?? 0),
            ])->all();

            $rows[] = ['TOTAL', '', (int) $laporan->sum('saldo_awal_kas'), $totalSetoran, $totalPenarikan, (int) $laporan->sum('saldo_akhir_sistem'), (int) $laporan->sum('saldo_akhir_fisik'), '', '', ''];

            $detailHeaders = ['Tanggal', 'Waktu', 'No. Struk', 'Teller', 'No. Rekening', 'Nasabah', 'Jenis', 'Debit (Rp)', 'Kredit (Rp)', 'Saldo (Rp)'];

            $detailRows = $mutasi->map(fn ($m) => [
                $m['tanggal']?->format('Y-m-d'),
                $m['waktu']?->format('H:i'),
                $m['no_struk'],
                $m['teller'],
                $m['rekening'],
                $m['nasabah'],
                $m['jenis'],
                $m['debit'],
                $m['kredit'],
                $m['saldo'],
            ])->all();

            array_unshift($detailRows, ['SALDO AWAL', '', '', '', '', '', '', '', '', $saldoAwal]);
            $detailRows[] = ['', '', '', '', '', '', 'TOTAL', (int) $mutasi->sum('debit'), (int) $mutasi->sum('kredit'), (int) ($mutasi->isNotEmpty() ? $mutasi->last()['saldo'] : $saldoAwal)];

            return ExcelExport::downloadSheets(
                'laporan-harian-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d'),
                [
                    [
                        'name' => 'Rekap Harian',
                        'headers' => $headers,
                        'rows' => $rows,
                        'options' => [
                            'title' => 'LAPORAN HARIAN',
                            'subtitle' => 'Periode '.$mulai->format('d/m/Y').' s.d. '.$sampai->format('d/m/Y').($tellerLabel ? ' | Teller: '.$tellerLabel : ''),
                        ],
                    ],
                    [
                        'name' => 'Buku Besar',
                        'headers' => $detailHeaders,
                        'rows' => $detailRows,
                        'options' => [
                            'title' => 'BUKU BESAR SIMPANAN / RINCIAN TRANSAKSI',
                            'subtitle' => $detailSubtitle,
                        ],
                    ],
                ]
            );
        }

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.harian', compact(
                'mulai',
                'sampai',
                'laporan',
                'saldoAwal',
                'mutasi',
                'totalSetoran',
                'totalPenarikan',
                'totalTransaksi',
                'nasabah',
                'jumlahHari',
                'rekapStatus',
                'saldoAwalPeriode',
                'saldoAkhirPeriode',
                'totalSelisih',
                'laporanDenganRincianFisik',
                'laporanFisikTanpaRincian',
                'laporanFisikBelumDiisi',
                'tellerLabel',
            ));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('laporan-harian-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d').'.pdf');
        }

        $nasabahList = Nasabah::orderBy('nama_siswa')->get();
        $tellerList = User::where('role', User::ROLE_TELLER)->orderBy('nama_petugas')->get(['id', 'nama_petugas']);

        return view('supervisor.laporan.harian', compact(
            'mulai',
            'sampai',
            'laporan',
            'saldoAwal',
            'mutasi',
            'totalSetoran',
            'totalPenarikan',
            'totalTransaksi',
            'nasabah',
            'nasabahList',
            'tellerList',
            'tellerTerpilih',
            'jumlahHari',
            'rekapStatus',
            'saldoAwalPeriode',
            'saldoAkhirPeriode',
            'totalSelisih',
            'laporanDenganRincianFisik',
            'laporanFisikTanpaRincian',
            'laporanFisikBelumDiisi',
        ));
    }

    public function bukuBesar(Request $request): View|Response|RedirectResponse
    {
        $mulai = $request->date('mulai') ?? today()->startOfMonth();
        $sampai = $request->date('sampai') ?? today();

        $bukuBesar = $this->dataBukuBesar($mulai, $sampai);
        $saldoAwal = $bukuBesar['saldoAwal'];
        $mutasi = $bukuBesar['mutasi'];
        $running = $mutasi->isNotEmpty() ? $mutasi->last()['saldo'] : $saldoAwal;

        if ($request->input('format') === 'excel') {
            $headers = ['Tanggal', 'No. Struk', 'Nasabah', 'No. Rekening', 'Jenis', 'Debit (Rp)', 'Kredit (Rp)', 'Saldo (Rp)'];

            $rows = $mutasi->map(fn ($m) => [
                $m['tanggal']?->format('Y-m-d'),
                $m['no_struk'],
                $m['nasabah'],
                $m['rekening'],
                $m['jenis'],
                $m['debit'],
                $m['kredit'],
                $m['saldo'],
            ])->all();

            array_unshift($rows, ['SALDO AWAL', $mulai->format('Y-m-d'), '', '', '', '', '', $saldoAwal]);
            $rows[] = ['', '', '', '', 'SALDO AKHIR', '', '', $running];

            return ExcelExport::download(
                'buku-besar-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d'),
                $headers,
                $rows,
                'Buku Besar'
            );
        }

        if ($request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.buku-besar', compact('mulai', 'sampai', 'saldoAwal', 'mutasi'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('buku-besar-'.$mulai->format('Y-m-d').'-'.$sampai->format('Y-m-d').'.pdf');
        }

        return view('supervisor.laporan.buku-besar', compact('mulai', 'sampai', 'saldoAwal', 'mutasi'));
    }

    /**
     * Data buku besar simpanan untuk periode tertentu.
     *
     * @return array{saldoAwal: int, mutasi: Collection<int, array<string, mixed>>}
     */
    protected function dataBukuBesar(Carbon $mulai, Carbon $sampai, ?Nasabah $nasabah = null, ?int $tellerId = null): array
    {
        $transaksi = Transaksi::with(['nasabah', 'user'])
            ->whereBetween('tanggal', [$mulai, $sampai])
            ->whereNull('koreksi_id')
            ->when($nasabah, fn ($query) => $query->where('id_nasabah', $nasabah->id))
            ->when($tellerId, fn ($query) => $query->where('id_user', $tellerId))
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $saldoAwal = (int) Transaksi::where('tanggal', '<', $mulai)
            ->whereNull('koreksi_id')
            ->when($nasabah, fn ($query) => $query->where('id_nasabah', $nasabah->id))
            ->when($tellerId, fn ($query) => $query->where('id_user', $tellerId))
            ->sum(DB::raw('CASE WHEN jenis_trans = "setoran" THEN nominal ELSE -nominal END'));

        $running = $saldoAwal;
        $mutasi = $transaksi->map(function (Transaksi $t) use (&$running) {
            $nominal = (int) $t->nominal;
            $debit = $t->jenis_trans === Transaksi::JENIS_SETORAN ? $nominal : 0;
            $kredit = $t->jenis_trans === Transaksi::JENIS_PENARIKAN ? $nominal : 0;
            $running += $debit - $kredit;

            return [
                'tanggal' => $t->tanggal,
                'waktu' => $t->created_at,
                'no_struk' => $t->no_struk,
                'teller' => $t->user?->nama_petugas ?? '-',
                'nasabah' => $t->nasabah?->nama_siswa ?? '-',
                'rekening' => $t->nasabah?->no_rekening ?? '-',
                'jenis' => $t->jenis_label,
                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => $running,
            ];
        });

        return ['saldoAwal' => $saldoAwal, 'mutasi' => $mutasi];
    }

    public function mutasiNasabah(Request $request): View|Response
    {
        $nasabahList = Nasabah::orderBy('nama_siswa')->get();

        $nasabahId = $request->integer('nasabah_id');
        $nasabah = Nasabah::find($nasabahId);

        $mutasi = collect();
        $saldoAwal = 0;

        if ($nasabah) {
            $mulai = $request->date('mulai') ?? today()->startOfMonth();
            $sampai = $request->date('sampai') ?? today();

            $mutasi = $nasabah->transaksi()
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

            $mutasi = $mutasi->map(function ($t) use (&$running) {
                $nominal = (int) $t->nominal;
                $running += $t->jenis_trans === 'setoran' ? $nominal : -$nominal;

                return [
                    'tanggal' => $t->tanggal,
                    'no_struk' => $t->no_struk,
                    'jenis' => $t->jenis_label,
                    'debit' => $t->jenis_trans === 'setoran' ? $nominal : 0,
                    'kredit' => $t->jenis_trans === 'penarikan' ? $nominal : 0,
                    'saldo' => $running,
                ];
            });
        }

        if ($nasabah && $request->input('format') === 'excel') {
            $headers = ['Tanggal', 'No. Struk', 'Jenis', 'Debit (Rp)', 'Kredit (Rp)', 'Saldo (Rp)'];

            $rows = $mutasi->map(fn ($m) => [
                $m['tanggal']?->format('Y-m-d'),
                $m['no_struk'],
                $m['jenis'],
                $m['debit'],
                $m['kredit'],
                $m['saldo'],
            ])->all();

            return ExcelExport::download(
                'mutasi-'.($nasabah->no_rekening ?? 'nasabah'),
                $headers,
                $rows,
                'Mutasi '.($nasabah->no_rekening ?? '')
            );
        }

        if ($nasabah && $request->input('format') === 'pdf') {
            $pdf = Pdf::loadView('supervisor.laporan.pdf.mutasi', compact('nasabah', 'saldoAwal', 'mutasi'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('mutasi-'.($nasabah->no_rekening ?? 'nasabah').'.pdf');
        }

        return view('supervisor.laporan.mutasi', compact('nasabahList', 'nasabah', 'saldoAwal', 'mutasi'));
    }

    public function saldoProduk(Request $request): View|Response|RedirectResponse
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

        return view('supervisor.laporan.saldo-produk', compact('tanggal', 'ringkasan', 'totalNasabah', 'totalSeluruh'));
    }

    public function laporanTahunan(Request $request): View|Response|RedirectResponse
    {
        $tahun = $request->integer('tahun') ?: now()->year;

        $data = Transaksi::whereYear('tanggal', $tahun)
            ->where('koreksi_id', null)
            ->selectRaw('jenis_trans, COUNT(*) as jumlah, SUM(nominal) as nominal')
            ->groupBy('jenis_trans')
            ->get()
            ->keyBy('jenis_trans');

        $totalSetoran = (int) ($data['setoran']->nominal ?? 0);
        $totalPenarikan = (int) ($data['penarikan']->nominal ?? 0);
        $jumlahTransaksi = (int) ($data->sum('jumlah'));
        $nasabahBaru = (int) Nasabah::whereYear('created_at', $tahun)->count();
        $saldoAkhir = (int) Nasabah::sum('saldo');

        $perBulan = [];
        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $m = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);
            $perBulan[] = [
                'bulan' => Carbon::create()->month($bulan)->translatedFormat('F'),
                'setoran' => (int) Transaksi::whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('jenis_trans', 'setoran')->where('koreksi_id', null)->sum('nominal'),
                'penarikan' => (int) Transaksi::whereYear('tanggal', $tahun)->whereMonth('tanggal', $bulan)->where('jenis_trans', 'penarikan')->where('koreksi_id', null)->sum('nominal'),
            ];
        }

        if ($request->input('format') === 'excel') {
            $headers = ['Bulan', 'Setoran (Rp)', 'Penarikan (Rp)'];

            $rows = $perBulan;

            $rows[] = ['TOTAL', $totalSetoran, $totalPenarikan];

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

        return view('supervisor.laporan.tahunan', compact('tahun', 'totalSetoran', 'totalPenarikan', 'jumlahTransaksi', 'nasabahBaru', 'saldoAkhir', 'perBulan'));
    }

    protected function grafikTransaksiBulanan(): array
    {
        $labels = [];
        $setoran = [];
        $penarikan = [];

        for ($i = 11; $i >= 0; $i--) {
            $bulan = now()->subMonthsNoOverflow($i);
            $labels[] = $bulan->translatedFormat('M Y');

            $setoran[] = (int) Transaksi::whereYear('tanggal', $bulan->year)
                ->whereMonth('tanggal', $bulan->month)
                ->where('jenis_trans', 'setoran')
                ->sum('nominal');

            $penarikan[] = (int) Transaksi::whereYear('tanggal', $bulan->year)
                ->whereMonth('tanggal', $bulan->month)
                ->where('jenis_trans', 'penarikan')
                ->sum('nominal');
        }

        return compact('labels', 'setoran', 'penarikan');
    }
}
