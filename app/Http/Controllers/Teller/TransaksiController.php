<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Konfigurasi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\UserNotification;
use App\Support\InfoBank;
use App\Support\NomorGenerator;
use App\Support\QrisNasabah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransaksiController extends Controller
{
    public function index(Request $request): View
    {
        $nasabah = null;
        $transaksiTerakhir = Transaksi::with(['nasabah', 'user'])
            ->where('id_user', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        return view('teller.transaksi.index', [
            'nasabah' => $nasabah,
            'transaksiTerakhir' => $transaksiTerakhir,
            'tokenForm' => $this->tokenForm($request),
        ]);
    }

    public function formNasabah(Request $request, Nasabah $nasabah): View|RedirectResponse
    {
        if ($nasabah->isArsip()) {
            return redirect()->route('teller.dashboard')
                ->with('error', 'Nasabah ini tidak aktif dan tidak dapat diproses.');
        }

        return view('teller.transaksi.form', [
            'nasabah' => $nasabah,
            'tokenForm' => $this->tokenForm($request),
            'logo' => InfoBank::logoUrl(),
            'bank' => [
                'nama' => InfoBank::NAMA,
                'lembaga' => InfoBank::LEMBAGA,
                'alamat' => InfoBank::ALAMAT,
            ],
        ]);
    }

    public function cariNasabah(Request $request): View|JsonResponse
    {
        $request->validate([
            'cari' => ['required', 'string'],
        ]);

        $keyword = trim($request->input('cari'));

        // QRIS / QR Code nasabah: isi QR adalah nomor rekening dan stabil
        // (tanpa token/waktu), sehingga QR yang sama bisa dipindai berkali-
        // kali dan selalu menunjuk nasabah yang sama. Hanya nasabah AKTIF
        // (belum diarsipkan) yang boleh dilayani.
        $nasabah = QrisNasabah::cari($keyword);

        $transaksiTerakhir = Transaksi::with(['nasabah', 'user'])
            ->where('id_user', Auth::id())
            ->latest()
            ->take(10)
            ->get();

        if ($request->ajax() || $request->wantsJson()) {
            if (! $nasabah) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nasabah dengan nomor rekening/NIS "'.$keyword.'" tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nasabah ditemukan: '.$nasabah->nama_siswa,
                'nasabah' => [
                    'id' => $nasabah->id,
                    'nama_siswa' => $nasabah->nama_siswa,
                    'no_rekening' => $nasabah->no_rekening,
                ],
            ]);
        }

        return view('teller.transaksi.index', [
            'nasabah' => $nasabah,
            'transaksiTerakhir' => $transaksiTerakhir,
            'tokenForm' => $this->tokenForm($request),
        ])
            ->with($nasabah ? 'success' : 'error', $nasabah
                ? 'Nasabah ditemukan: '.$nasabah->nama_siswa
                : 'Nasabah dengan nomor rekening/NIS "'.$keyword.'" tidak ditemukan.');
    }

    public function proses(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_nasabah' => ['required', 'exists:nasabah,id'],
            'jenis_trans' => ['required', 'in:setoran,penarikan'],
            'nominal' => ['required', 'integer', 'min:1'],
            'token_transaksi' => ['required', 'string', 'max:64'],
            'konfirmasi_h1' => ['nullable', 'boolean'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $prior = Transaksi::where('token_otorisasi', $validated['token_transaksi'])->first();
        if ($prior) {
            if ((int) $prior->id_user !== (int) Auth::id()) {
                abort(403);
            }

            return redirect()->route('teller.transaksi.otorisasi.tunggu', $prior);
        }

        $nasabah = Nasabah::with('user')->findOrFail($validated['id_nasabah']);
        if ($nasabah->isArsip()) {
            return back()->with('error', 'Nasabah ini sedang tidak aktif (diarsipkan). Transaksi dibatalkan.');
        }

        $nominal = (int) $validated['nominal'];
        $saldoMinimum = Konfigurasi::saldoMinimum();
        $batasPenarikanBesar = Konfigurasi::batasPenarikanBesar();
        if ($validated['jenis_trans'] === 'penarikan') {
            if ($nominal > $nasabah->saldo || ($nasabah->saldo - $nominal) < $saldoMinimum) {
                return back()->with('error', 'Saldo nasabah tidak cukup atau akan berada di bawah saldo minimum.');
            }

            if ($nominal > $batasPenarikanBesar && ! $request->boolean('konfirmasi_h1')) {
                return back()->with('error', 'Penarikan besar wajib memiliki konfirmasi nasabah H-1.');
            }
        }

        try {
            $transaksi = DB::transaction(function () use ($nasabah, $validated, $nominal, $request) {
                $noStruk = NomorGenerator::struk();
                $transaksi = Transaksi::create([
                    'id_nasabah' => $nasabah->id,
                    'id_user' => Auth::id(),
                    'tanggal' => today(),
                    'jenis_trans' => $validated['jenis_trans'],
                    'nominal' => $nominal,
                    'konfirmasi_h1' => $request->boolean('konfirmasi_h1'),
                    'catatan' => $request->input('catatan'),
                    'no_struk' => $noStruk,
                    'status' => Transaksi::STATUS_PENDING,
                    'kadaluarsa_pada' => now()->addMinutes(5),
                    'token_otorisasi' => $validated['token_transaksi'],
                ]);

                ActivityLog::catat(
                    Auth::id(),
                    'permintaan_otorisasi_transaksi',
                    ucfirst($validated['jenis_trans']).' Rp '.number_format($nominal, 0, ',', '.')." menunggu PIN nasabah {$nasabah->nama_siswa} (no referensi {$noStruk})."
                );

                return $transaksi;
            });

            $userNasabah = $nasabah->user ?? $nasabah->ensureUser();
            $nominalRupiah = 'Rp '.number_format($nominal, 0, ',', '.');
            $jenisLabel = ucfirst($validated['jenis_trans']);
            UserNotification::kirim(
                $userNasabah,
                UserNotification::TIPE_INFO,
                'Konfirmasi Transaksi Diperlukan',
                "{$jenisLabel} {$nominalRupiah} menunggu persetujuan Anda. Masukkan PIN pada aplikasi dalam 5 menit.",
                null,
                ['id' => (string) $transaksi->id, 'jenis' => $jenisLabel, 'nominal' => $nominalRupiah, 'tipe' => 'otorisasi'],
            );

            return redirect()->route('teller.transaksi.otorisasi.tunggu', $transaksi);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim permintaan transaksi: '.$e->getMessage());
        }
    }

    public function tungguOtorisasi(Transaksi $transaksi): View
    {
        abort_unless((int) $transaksi->id_user === (int) Auth::id(), 403);

        return view('teller.transaksi.otorisasi-menunggu', compact('transaksi'));
    }

    public function statusOtorisasi(Transaksi $transaksi): JsonResponse
    {
        abort_unless((int) $transaksi->id_user === (int) Auth::id(), 403);

        $expiry = $transaksi->kadaluarsa_pada ?? $transaksi->created_at?->copy()->addMinutes(5);
        if ($transaksi->status === Transaksi::STATUS_PENDING && $expiry && now()->gte($expiry)) {
            $updated = Transaksi::whereKey($transaksi->id)
                ->where('status', Transaksi::STATUS_PENDING)
                ->update(['status' => Transaksi::STATUS_KEDALUWARSA, 'updated_at' => now()]);

            if ($updated) {
                UserNotification::kirim($transaksi->id_user, UserNotification::TIPE_WARNING, 'Otorisasi Kedaluwarsa', 'Permintaan transaksi '.$transaksi->no_struk.' kedaluwarsa karena tidak dikonfirmasi dalam 5 menit.', route('teller.transaksi.index'));
                ActivityLog::catat($transaksi->id_user, 'otorisasi_kedaluwarsa', 'Permintaan transaksi '.$transaksi->no_struk.' kedaluwarsa.');
            }

            $transaksi->refresh();
        }

        return response()->json([
            'status' => match ($transaksi->status) {
                Transaksi::STATUS_PENDING => 'menunggu',
                Transaksi::STATUS_APPROVED => 'berhasil',
                Transaksi::STATUS_DITOLAK => 'ditolak',
                Transaksi::STATUS_KEDALUWARSA => 'kedaluwarsa',
                default => 'gagal',
            },
            'message' => match ($transaksi->status) {
                Transaksi::STATUS_PENDING => 'Menunggu PIN nasabah.',
                Transaksi::STATUS_APPROVED => 'Transaksi berhasil disetujui.',
                Transaksi::STATUS_DITOLAK => 'Nasabah menolak transaksi atau batas percobaan PIN tercapai.',
                Transaksi::STATUS_KEDALUWARSA => 'Permintaan transaksi telah kedaluwarsa.',
                default => 'Transaksi tidak dapat diproses.',
            },
            'receipt_url' => $transaksi->status === Transaksi::STATUS_APPROVED ? route('teller.struk', $transaksi) : null,
            'expires_at' => $transaksi->kadaluarsa_pada?->toISOString(),
        ]);
    }

    /**
     * Token unik per render form transaksi. Request POST ganda dengan token
     * yang sama akan diarahkan ke permintaan otorisasi yang sudah dibuat.
     */
    protected function tokenForm(Request $request): string
    {
        $token = Str::random(40);
        $request->session()->put('form_transaksi_token', $token);

        return $token;
    }
}
