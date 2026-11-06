<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\VerifikasiHarian;
use App\Support\VerifikasiHarianProses;
use App\Support\VerifikasiHarianService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Verifikasi transaksi supervisor PER HARI.
 *
 * Transaksi dikelompokkan berdasarkan tanggal. Supervisor memilih tanggal,
 * melihat transaksi beserta ringkasan harian, lalu menerima atau menolak
 * verifikasi. Tanggal yang ditolak dapat ditinjau ulang; tanggal yang diterima
 * menjadi riwayat tetap.
 */
class VerifikasiHarianController extends Controller
{
    public function index(Request $request): View
    {
        $tanggal = $request->query('tanggal')
            ? Carbon::parse($request->query('tanggal'))->startOfDay()
            : null;

        $daftar = VerifikasiHarianService::daftarTanggal(30);
        if ($tanggal && ! $daftar->contains(fn ($row) => $row['tanggal'] === $tanggal->toDateString())) {
            $daftar->prepend([
                'tanggal' => $tanggal->toDateString(),
                'ringkasan' => VerifikasiHarianService::ringkasan($tanggal),
                'sudah' => VerifikasiHarian::whereDate('tanggal', $tanggal)->first(),
            ]);
        }
        $hubContext = $this->hubContext($request);

        $terpilih = null;
        $ringkasan = null;
        $transaksi = collect();
        $verifikasi = null;
        $hanyaBelum = $request->boolean('belum');

        if ($tanggal) {
            $verifikasi = VerifikasiHarian::whereDate('tanggal', $tanggal)->first();
            $ringkasan = VerifikasiHarianService::ringkasan($tanggal);
            $terpilih = $tanggal;

            // Tampilkan seluruh transaksi tanggal terpilih untuk ditinjau ulang atau dibaca sebagai riwayat.
            $transaksi = VerifikasiHarianService::transaksiTanggal($tanggal)
                ->filter(fn ($t) => $t->status === Transaksi::STATUS_APPROVED)
                ->values();
        }

        return view('verifikasi-harian.index', compact(
            'daftar',
            'terpilih',
            'ringkasan',
            'transaksi',
            'verifikasi',
            'hanyaBelum',
            'hubContext',
        ));
    }

    public function verifikasi(Request $request, string $tanggal): RedirectResponse
    {
        $keputusan = $request->input('keputusan', VerifikasiHarian::STATUS_DITERIMA);
        $request->validate([
            'keputusan' => ['nullable', 'in:diterima,ditolak'],
            'catatan' => ['nullable', 'string', 'max:500', 'required_if:keputusan,ditolak'],
        ], [
            'catatan.required_if' => 'Catatan wajib diisi saat menolak verifikasi.',
        ]);

        try {
            $hasil = VerifikasiHarianProses::verifikasi(
                $tanggal,
                $request->user(),
                $request->input('catatan'),
                $keputusan,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $pesan = $hasil->status === VerifikasiHarian::STATUS_DITOLAK
            ? 'Verifikasi tanggal '.$hasil->tanggal->format('d/m/Y').' ditolak. Teller terkait telah menerima notifikasi.'
            : 'Transaksi tanggal '.$hasil->tanggal->format('d/m/Y').' berhasil diterima. Verifikasi ulang tidak diizinkan.';

        if ($hubContext = $this->hubContext($request)) {
            return redirect()
                ->route('supervisor.pusat-verifikasi', $hubContext)
                ->with('success', $pesan);
        }

        return redirect()
            ->route('verifikasi.index', ['tanggal' => $hasil->tanggal->toDateString()])
            ->with('success', $pesan);
    }

    private function hubContext(Request $request): ?array
    {
        if (! $request->boolean('hub')) {
            return null;
        }

        return [
            'tab' => 'transaksi-harian',
            'status' => (string) $request->input('status', 'belum'),
            'cari' => (string) $request->input('cari', ''),
        ];
    }
}
