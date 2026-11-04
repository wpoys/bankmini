<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LaporanHarian;
use App\Models\UserNotification;
use App\Support\KodeAkun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $laporan = LaporanHarian::with('teller')
            ->when($request->status && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('supervisor.verifikasi.index', compact('laporan'));
    }

    public function show(LaporanHarian $laporan): View
    {
        $laporan->load([
            'teller',
            'detailLaporan.transaksi.nasabah',
            'detailLaporan.transaksi.user',
            'approver',
            'rekonsiliasi.detail',
        ]);

        return view('supervisor.verifikasi.show', compact('laporan'));
    }

    public function approve(Request $request, LaporanHarian $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_supervisor' => ['nullable', 'string', 'max:1000'],
        ]);

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

        return redirect()->route('supervisor.verifikasi.index')
            ->with('success', 'Laporan berhasil disetujui.');
    }

    public function reject(Request $request, LaporanHarian $laporan): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_supervisor' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_supervisor.required' => 'Alasan penolakan wajib diisi.',
        ]);

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

        return redirect()->route('supervisor.verifikasi.index')
            ->with('success', 'Laporan ditolak dengan catatan.');
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

        return redirect()->route('supervisor.verifikasi.index')
            ->with('success', 'Laporan ditahan (pending) tanpa mengunci akun teller.');
    }

    public function audit(): View
    {
        $logs = ActivityLog::with('user')
            ->latest()
            ->take(100)
            ->get();

        return view('supervisor.audit', compact('logs'));
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
