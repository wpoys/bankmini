<?php

namespace App\Http\Controllers;

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
 * melihat transaksi yang belum diverifikasi beserta ringkasan harian, lalu
 * memverifikasi satu kali. Tanggal yang sudah diverifikasi tidak dapat
 * diverifikasi ulang, namun riwayat tetap dapat dilihat.
 */
class VerifikasiHarianController extends Controller
{
    public function index(Request $request): View
    {
        $tanggal = $request->query('tanggal')
            ? Carbon::parse($request->query('tanggal'))->startOfDay()
            : null;

        $daftar = VerifikasiHarianService::daftarTanggal(30);

        $terpilih = null;
        $ringkasan = null;
        $transaksi = collect();
        $verifikasi = null;
        $hanyaBelum = $request->boolean('belum');

        if ($tanggal) {
            $verifikasi = \App\Models\VerifikasiHarian::whereDate('tanggal', $tanggal)->first();
            $ringkasan = VerifikasiHarianService::ringkasan($tanggal);
            $terpilih = $tanggal;

            // Sebelum terverifikasi: daftar transaksi yang belum diverifikasi.
            // Setelah terverifikasi: seluruh transaksi hari itu ditampilkan
            // sebagai riwayat read-only (tidak dapat diverifikasi ulang).
            $transaksi = VerifikasiHarianService::transaksiTanggal($tanggal)
                ->reject(fn ($t) => $t->status === \App\Models\Transaksi::STATUS_DITOLAK)
                ->values();
        }

        return view('verifikasi-harian.index', compact(
            'daftar',
            'terpilih',
            'ringkasan',
            'transaksi',
            'verifikasi',
            'hanyaBelum',
        ));
    }

    /**
     * Verifikasi satu tanggal. Idempoten: tidak dapat diulang.
     */
    public function verifikasi(Request $request, string $tanggal): RedirectResponse
    {
        $request->validate([
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $hasil = VerifikasiHarianProses::verifikasi($tanggal, $request->user(), $request->input('catatan'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('verifikasi.index', ['tanggal' => $hasil->tanggal->toDateString()])
            ->with('success', 'Transaksi tanggal '.$hasil->tanggal->format('d/m/Y').' berhasil diverifikasi. Verifikasi ulang tidak diizinkan.');
    }
}
