<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Konfigurasi;
use App\Models\KoreksiTransaksi;
use App\Models\LaporanHarian;
use App\Models\PermintaanResetPin;
use App\Models\Transaksi;
use App\Models\UserNotification;
use App\Support\BrankasService;
use App\Support\KodeAkun;
use App\Support\VerifikasiHarianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function pusat(Request $request): View
    {
        $tab = (string) $request->query('tab', 'laporan');
        $tabsValid = ['laporan', 'transaksi-harian', 'koreksi', 'pin', 'aktivitas'];
        if (! in_array($tab, $tabsValid, true)) {
            $tab = 'laporan';
        }

        $search = trim((string) $request->query('cari', ''));
        $status = (string) $request->query('status', 'menunggu_approval');
        $availableStatuses = match ($tab) {
            'laporan' => ['menunggu_approval', 'disetujui', 'ditolak', 'pending', 'draft'],
            'transaksi-harian' => ['belum', 'diterima', 'ditolak'],
            'koreksi' => ['menunggu_approval', 'disetujui', 'ditolak'],
            'pin' => [PermintaanResetPin::STATUS_PENDING, PermintaanResetPin::STATUS_DIPROSES, PermintaanResetPin::STATUS_DITOLAK, PermintaanResetPin::STATUS_DIBATALKAN],
            default => [],
        };
        if ($tab !== 'aktivitas' && ! in_array($status, $availableStatuses, true)) {
            $status = $tab === 'transaksi-harian' ? 'belum' : ($availableStatuses[0] ?? '');
        }

        $laporanPending = (int) LaporanHarian::where('status', 'menunggu_approval')->count();
        $koreksiPending = (int) KoreksiTransaksi::where('status', 'menunggu_approval')->count();
        $pinPending = (int) PermintaanResetPin::where('status', PermintaanResetPin::STATUS_PENDING)->count();
        $daftarTanggalVerifikasi = VerifikasiHarianService::daftarTanggal(500);
        $tanggalBelumVerifikasi = $daftarTanggalVerifikasi
            ->filter(fn ($tanggal) => $tanggal['sudah'] === null || $tanggal['sudah']->status === 'ditolak')
            ->count();
        $laporan = null;
        $tanggalList = null;
        $koreksi = null;
        $kodeSandiTersetel = Konfigurasi::kodeSandiKoreksiTersetel();
        $permintaanPin = null;
        $logs = null;

        if ($tab === 'laporan') {
            $laporan = LaporanHarian::with('teller')
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('tanggal', 'like', '%'.$search.'%')
                        ->orWhereHas('teller', fn ($teller) => $teller->where('nama_petugas', 'like', '%'.$search.'%'));
                }))
                ->latest()->paginate(10)->withQueryString();
        } elseif ($tab === 'transaksi-harian') {
            $tanggalItems = $daftarTanggalVerifikasi
                ->map(function ($item) {
                    $tanggal = Carbon::parse($item['tanggal'])->startOfDay();
                    $item['tanggal_obj'] = $tanggal;
                    $item['verifikasi'] = $item['sudah'];
                    $item['ringkasan'] = VerifikasiHarianService::ringkasan($tanggal);

                    return $item;
                })
                ->filter(function ($item) use ($status) {
                    $verifikasi = $item['verifikasi'];

                    return match ($status) {
                        'diterima' => $verifikasi?->status === 'diterima',
                        'ditolak' => $verifikasi?->status === 'ditolak',
                        default => $verifikasi === null || $verifikasi->status === 'ditolak',
                    };
                })
                ->when($search !== '', fn ($items) => $items->filter(fn ($item) => $item['tanggal_obj']->format('d/m/Y') === $search || $item['tanggal_obj']->toDateString() === $search))
                ->values();
            $tanggalList = new LengthAwarePaginator(
                $tanggalItems->forPage(Paginator::resolveCurrentPage(), 10),
                $tanggalItems->count(),
                10,
                Paginator::resolveCurrentPage(),
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $tanggalList->through(function ($item) {
                $item['transaksi'] = VerifikasiHarianService::transaksiTanggal($item['tanggal_obj'])
                    ->filter(fn ($transaksi) => $transaksi->status === Transaksi::STATUS_APPROVED)
                    ->values();

                return $item;
            });
        } elseif ($tab === 'koreksi') {
            $koreksi = KoreksiTransaksi::with(['transaksi', 'nasabah', 'pengaju'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('alasan', 'like', '%'.$search.'%')
                        ->orWhereHas('nasabah', fn ($nasabah) => $nasabah->where('nama_siswa', 'like', '%'.$search.'%')->orWhere('no_rekening', 'like', '%'.$search.'%'))
                        ->orWhereHas('pengaju', fn ($pengaju) => $pengaju->where('nama_petugas', 'like', '%'.$search.'%'))
                        ->orWhereHas('transaksi', fn ($transaksi) => $transaksi->where('no_struk', 'like', '%'.$search.'%'));
                }))
                ->latest()->paginate(10)->withQueryString();
        } elseif ($tab === 'pin') {
            $permintaanPin = PermintaanResetPin::with(['nasabah.user', 'pemroses', 'teller'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('keterangan', 'like', '%'.$search.'%')
                        ->orWhereHas('nasabah', fn ($nasabah) => $nasabah->where('nama_siswa', 'like', '%'.$search.'%')->orWhere('no_rekening', 'like', '%'.$search.'%')->orWhere('nis', 'like', '%'.$search.'%'));
                }))
                ->latest()->paginate(10)->withQueryString();
        } else {
            $logs = ActivityLog::with('user')
                ->when($search !== '', fn ($query) => $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('aktivitas', 'like', '%'.$search.'%')
                        ->orWhere('deskripsi', 'like', '%'.$search.'%')
                        ->orWhere('ip_address', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($user) => $user->where('nama_petugas', 'like', '%'.$search.'%'));
                }))
                ->latest()->take(100)->get();
        }

        return view('supervisor.pusat-verifikasi', compact(
            'tab', 'status', 'search', 'laporanPending', 'koreksiPending', 'pinPending',
            'tanggalBelumVerifikasi', 'laporan', 'tanggalList',
            'koreksi', 'kodeSandiTersetel', 'permintaanPin', 'logs',
        ));
    }

    public function index(Request $request): View
    {
        $laporan = LaporanHarian::with('teller')
            ->when($request->status && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('supervisor.verifikasi.index', compact('laporan'));
    }

    public function show(Request $request, LaporanHarian $laporan): View
    {
        $hubContext = $this->validHubContext($request);
        $laporan->load([
            'teller',
            'detailLaporan.transaksi.nasabah',
            'detailLaporan.transaksi.user',
            'approver',
            'rekonsiliasi.detail',
        ]);

        return view('supervisor.verifikasi.show', compact('laporan', 'hubContext'));
    }

    public function approve(Request $request, LaporanHarian $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_supervisor' => ['nullable', 'string', 'max:1000'],
        ]);
        $hubContext = $this->validHubContext($request);

        if ($laporan->status !== 'menunggu_approval') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu verifikasi.');
        }

        if ($laporan->selisih !== null && $laporan->selisih != 0) {
            return back()->with('error', 'Laporan memiliki selisih kas (geseh) sebesar Rp '.number_format(abs((int) $laporan->selisih), 0, ',', '.').'. Pengesahan ditolak — gunakan aksi Tahan (Pending) agar teller memperbarui laporan, atau Tolak dengan catatan.');
        }

        $jumlahBrankas = $laporan->kas_dipindahkan !== null ? (int) $laporan->kas_dipindahkan : 0;

        DB::transaction(function () use ($laporan, $validated, $jumlahBrankas) {
            $laporan->update([
                'status' => 'disetujui',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'catatan_supervisor' => $validated['catatan_supervisor'],
            ]);

            if ($jumlahBrankas > 0) {
                BrankasService::setorDariLaporan($jumlahBrankas, (int) $laporan->teller_id, (int) $laporan->id);
                $laporan->update([
                    'kas_dipindahkan' => $jumlahBrankas,
                    'kas_dipindahkan_oleh' => auth()->id(),
                    'kas_dipindahkan_at' => now(),
                ]);

                $this->catatJurnalPindahBrankas($jumlahBrankas);
            }
        });

        ActivityLog::catat(auth()->id(), 'approve_laporan', "Laporan harian tanggal {$laporan->tanggal} dari {$laporan->teller->nama_petugas} disetujui.");

        if ($jumlahBrankas > 0) {
            ActivityLog::catat(auth()->id(), 'pindah_brankas', 'Kas sebesar Rp '.number_format($jumlahBrankas, 0, ',', '.')." dipindahkan ke brankas utama (ditetapkan Teller) untuk laporan tanggal {$laporan->tanggal}.");
        }

        UserNotification::kirim(
            $laporan->teller_id,
            UserNotification::TIPE_SUCCESS,
            'Laporan Disetujui',
            "Laporan harian tanggal {$laporan->tanggal} Anda telah disetujui Supervisor.",
            route('teller.laporan.show', $laporan->id),
        );

        return $this->redirectAfterVerifikasi($hubContext, 'laporan', 'Laporan berhasil disetujui.');
    }

    public function reject(Request $request, LaporanHarian $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_supervisor' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_supervisor.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $hubContext = $this->validHubContext($request);

        if ($laporan->status !== 'menunggu_approval') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu verifikasi.');
        }

        $laporan->update([
            'status' => 'ditolak',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'catatan_supervisor' => $validated['catatan_supervisor'],
        ]);

        ActivityLog::catat(auth()->id(), 'tolak_laporan', "Laporan harian tanggal {$laporan->tanggal} dari {$laporan->teller->nama_petugas} ditolak.");

        UserNotification::kirim(
            $laporan->teller_id,
            UserNotification::TIPE_DANGER,
            'Laporan Ditolak',
            "Laporan harian tanggal {$laporan->tanggal} Anda ditolak Supervisor. Catatan: ".($validated['catatan_supervisor'] ?: '-'),
            route('teller.laporan.show', $laporan->id),
        );

        return $this->redirectAfterVerifikasi($hubContext, 'laporan', 'Laporan ditolak dengan catatan.');
    }

    /**
     * Tahan (hold) laporan yang memiliki selisih kas tanpa mengunci akun
     * teller: teller tetap dapat piket dan memperbaiki laporan keesokan hari.
     */
    public function pending(Request $request, LaporanHarian $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_supervisor' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_supervisor.required' => 'Catatan penahanan wajib diisi.',
        ]);

        $hubContext = $this->validHubContext($request);

        if ($laporan->status !== 'menunggu_approval') {
            return back()->with('error', 'Laporan ini tidak sedang menunggu verifikasi.');
        }

        $laporan->update([
            'status' => 'pending',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'catatan_supervisor' => $validated['catatan_supervisor'],
        ]);

        ActivityLog::catat(
            auth()->id(),
            'pending_laporan',
            "Laporan harian tanggal {$laporan->tanggal} dari {$laporan->teller->nama_petugas} ditahan (pending) karena selisih kas Rp ".number_format(abs((int) $laporan->selisih), 0, ',', '.').'.'
        );

        UserNotification::kirim(
            $laporan->teller_id,
            UserNotification::TIPE_WARNING,
            'Laporan Ditahan (Pending)',
            "Laporan harian tanggal {$laporan->tanggal} ditahan karena selisih kas. Perbaiki rekonsiliasi lalu kirim ulang. Catatan: {$validated['catatan_supervisor']}",
            route('teller.laporan.show', $laporan->id),
        );

        return $this->redirectAfterVerifikasi($hubContext, 'laporan', 'Laporan ditahan (pending) tanpa mengunci akun teller.');
    }

    public function audit(): View
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->take(100)
            ->get();

        return view('supervisor.audit', compact('logs'));
    }

    /**
     * Konteks kembali ke Pusat Verifikasi setelah tindakan dilakukan.
     *
     * @return array{tab:string,status:string,cari:string}|null
     */
    protected function validHubContext(Request $request): ?array
    {
        $hub = $request->boolean('hub');

        if (! $hub) {
            return null;
        }

        $tab = (string) $request->input('tab', 'laporan');
        $tabsValid = ['laporan', 'transaksi-harian', 'koreksi', 'pin', 'aktivitas'];
        if (! in_array($tab, $tabsValid, true)) {
            $tab = 'laporan';
        }

        return [
            'tab' => $tab,
            'status' => (string) $request->input('status', ''),
            'cari' => (string) $request->input('cari', ''),
        ];
    }

    /**
     * @param  array{tab:string,status:string,cari:string}|null  $hubContext
     */
    protected function redirectAfterVerifikasi(?array $hubContext, string $tab, string $message): RedirectResponse
    {
        if ($hubContext !== null) {
            return redirect()->route('supervisor.pusat-verifikasi', [
                'tab' => $hubContext['tab'] ?: $tab,
                'status' => $hubContext['status'],
                'cari' => $hubContext['cari'],
            ])->with('success', $message);
        }

        return redirect()->route('supervisor.verifikasi.index')
            ->with('success', $message);
    }

    protected function catatJurnalPindahBrankas(int $jumlah): void
    {
        $entries = [
            ['kode_akun' => KodeAkun::BRANKAS, 'posisi' => 'debit', 'jumlah' => $jumlah],
            ['kode_akun' => KodeAkun::KAS, 'posisi' => 'kredit', 'jumlah' => $jumlah],
        ];

        foreach ($entries as $entry) {
            DB::table('jurnal_akuntansi')->insert([
                'id_transaksi' => null,
                'kode_akun' => $entry['kode_akun'],
                'posisi' => $entry['posisi'],
                'jumlah' => $entry['jumlah'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
