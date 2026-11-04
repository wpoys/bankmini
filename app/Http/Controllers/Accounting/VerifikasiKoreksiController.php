<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KoreksiTransaksi;
use App\Models\Konfigurasi;
use App\Models\Transaksi;
use App\Models\UserNotification;
use App\Support\KodeAkun;
use App\Support\NomorGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VerifikasiKoreksiController extends Controller
{
    public function index(Request $request): View
    {
        $koreksi = KoreksiTransaksi::with(['transaksi.nasabah', 'nasabah', 'pengaju', 'approver'])
            ->when($request->status && $request->status !== 'all', fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $menunggu = (int) KoreksiTransaksi::where('status', 'menunggu_approval')->count();

        $kodeSandiTersetel = Konfigurasi::kodeSandiKoreksiTersetel();

        return view('supervisor.koreksi.index', compact('koreksi', 'menunggu', 'kodeSandiTersetel'));
    }

    public function approve(Request $request, KoreksiTransaksi $koreksi): RedirectResponse
    {
        $kodeSandiWajib = Konfigurasi::kodeSandiKoreksiTersetel();

        $validated = $request->validate([
            'catatan_approver' => ['nullable', 'string', 'max:1000'],
            'kode_sandi_koreksi' => $kodeSandiWajib
                ? ['required', 'string', 'max:64']
                : ['nullable', 'string', 'max:64'],
        ], [
            'kode_sandi_koreksi.required' => 'Kode sandi koreksi wajib diisi untuk persetujuan.',
        ]);

        if ($koreksi->status !== 'menunggu_approval') {
            return back()->with('error', 'Pengajuan koreksi ini tidak sedang menunggu persetujuan.');
        }

        if ($kodeSandiWajib && ! Konfigurasi::validKodeSandiKoreksi($validated['kode_sandi_koreksi'] ?? null)) {
            return back()->withInput()->with('error', 'Kode sandi koreksi salah. Persetujuan dibatalkan.');
        }

        try {
            DB::transaction(function () use ($koreksi, $validated, $kodeSandiWajib) {
                $transaksiAsli = $koreksi->transaksi; // nullable: koreksi tanpa transaksi asli
                $nasabah = $koreksi->nasabah()->lockForUpdate()->firstOrFail();

                if ($koreksi->jenis) {
                    $jenisKoreksi = $koreksi->jenis === Transaksi::JENIS_KOREKSI_SETORAN
                        ? Transaksi::JENIS_SETORAN
                        : Transaksi::JENIS_PENARIKAN;
                } elseif ($transaksiAsli) {
                    // Data lama (tanpa jenis): turunkan otomatis sebagai pembalik.
                    $jenisKoreksi = $transaksiAsli->jenis_trans === Transaksi::JENIS_SETORAN
                        ? Transaksi::JENIS_PENARIKAN
                        : Transaksi::JENIS_SETORAN;
                } else {
                    throw new \RuntimeException('Koreksi tanpa transaksi asli wajib memiliki jenis (setoran/penarikan koreksi).');
                }

                $nominal = $koreksi->nominal ?? (int) ($transaksiAsli->nominal ?? 0);

                if ($nominal < 1) {
                    throw new \RuntimeException('Nominal koreksi tidak valid.');
                }

                if ($jenisKoreksi === Transaksi::JENIS_PENARIKAN && $nominal > $nasabah->saldo) {
                    throw new \RuntimeException('Saldo nasabah tidak mencukupi untuk koreksi penarikan ini.');
                }

                $transaksiKoreksi = Transaksi::create([
                    'id_nasabah' => $nasabah->id,
                    'id_user' => $koreksi->id_user,
                    'tanggal' => today(),
                    'jenis_trans' => $jenisKoreksi,
                    'nominal' => $nominal,
                    'no_struk' => NomorGenerator::struk(),
                    'status' => Transaksi::STATUS_APPROVED,
                    'koreksi_id' => $koreksi->id,
                ]);

                if ($jenisKoreksi === Transaksi::JENIS_PENARIKAN) {
                    $nasabah->decrement('saldo', $nominal);
                } else {
                    $nasabah->increment('saldo', $nominal);
                }

                $this->catatJurnal($transaksiKoreksi->id, $nominal, $jenisKoreksi);

                if ($transaksiAsli) {
                    $transaksiAsli->update(['dikoreksi' => true]);
                }

                $koreksi->update([
                    'status' => 'disetujui',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'catatan_approver' => $validated['catatan_approver'] ?? null,
                ]);

                $sasaran = $transaksiAsli
                    ? "transaksi {$transaksiAsli->no_struk}"
                    : 'koreksi tanpa transaksi asli atas nama '.$nasabah->nama_siswa;

                ActivityLog::catat(
                    auth()->id(),
                    'approve_koreksi',
                    "Koreksi {$sasaran} disetujui Supervisor".($kodeSandiWajib ? ' (kode sandi koreksi terverifikasi)' : '').". Transaksi koreksi {$transaksiKoreksi->no_struk} dibuat."
                );

                UserNotification::kirim(
                    $koreksi->id_user,
                    UserNotification::TIPE_SUCCESS,
                    'Koreksi Disetujui',
                    "Pengajuan koreksi {$sasaran} telah disetujui Supervisor. Saldo nasabah diperbarui.",
                    route('teller.koreksi.index'),
                );
            });

            return redirect()->route('supervisor.koreksi.index')
                ->with('success', 'Koreksi disetujui. Transaksi koreksi telah dibuat dan saldo nasabah diperbarui.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses koreksi: '.$e->getMessage());
        }
    }

    public function tolak(Request $request, KoreksiTransaksi $koreksi): RedirectResponse
    {
        $validated = $request->validate([
            'catatan_approver' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_approver.required' => 'Alasan penolakan wajib diisi.',
        ]);

        if ($koreksi->status !== 'menunggu_approval') {
            return back()->with('error', 'Pengajuan koreksi ini tidak sedang menunggu persetujuan.');
        }

        $koreksi->update([
            'status' => 'ditolak',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'catatan_approver' => $validated['catatan_approver'],
        ]);

        ActivityLog::catat(auth()->id(), 'tolak_koreksi', "Koreksi transaksi {$koreksi->transaksi_id} ditolak oleh Supervisor.");

        UserNotification::kirim(
            $koreksi->id_user,
            UserNotification::TIPE_DANGER,
            'Koreksi Ditolak',
            "Pengajuan koreksi transaksi {$koreksi->transaksi_id} ditolak Supervisor. Catatan: ".$validated['catatan_approver'],
            route('teller.koreksi.index'),
        );

        return redirect()->route('supervisor.koreksi.index')
            ->with('success', 'Pengajuan koreksi ditolak.');
    }

    protected function catatJurnal(int $idTransaksi, int $nominal, string $jenis): void
    {
        $entries = match ($jenis) {
            Transaksi::JENIS_SETORAN => [
                ['kode_akun' => KodeAkun::KAS, 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => KodeAkun::TABUNGAN, 'posisi' => 'kredit', 'jumlah' => $nominal],
            ],
            default => [
                ['kode_akun' => KodeAkun::TABUNGAN, 'posisi' => 'debit', 'jumlah' => $nominal],
                ['kode_akun' => KodeAkun::KAS, 'posisi' => 'kredit', 'jumlah' => $nominal],
            ],
        };

        foreach ($entries as $entry) {
            DB::table('jurnal_akuntansi')->insert([
                'id_transaksi' => $idTransaksi,
                'kode_akun' => $entry['kode_akun'],
                'posisi' => $entry['posisi'],
                'jumlah' => $entry['jumlah'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
