<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KoreksiTransaksi;
use App\Models\Nasabah;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KoreksiController extends Controller
{
    public function index(): View
    {
        $koreksi = KoreksiTransaksi::with(['transaksi.nasabah'])
            ->where('id_user', Auth::id())
            ->latest()
            ->paginate(15);

        return view('teller.koreksi.index', compact('koreksi'));
    }

    public function create(Request $request): View
    {
        $transaksi = Transaksi::with('nasabah')
            ->where('id_user', Auth::id())
            ->where('status', 'approved')
            ->where('dikoreksi', false)
            ->latest()
            ->take(50)
            ->get();

        $nasabahList = Nasabah::where('arsip', false)
            ->orderBy('nama_siswa')
            ->get(['id', 'nama_siswa', 'no_rekening', 'kelas']);

        return view('teller.koreksi.create', [
            'transaksi' => $transaksi,
            'nasabahList' => $nasabahList,
            'modal' => $request->boolean('modal'),
        ]);
    }

    public function transaksiNasabah(Nasabah $nasabah): JsonResponse
    {
        $transaksi = Transaksi::query()
            ->where('id_user', Auth::id())
            ->where('id_nasabah', $nasabah->id)
            ->where('status', Transaksi::STATUS_APPROVED)
            ->where('dikoreksi', false)
            ->latest()
            ->take(50)
            ->get();

        return response()->json([
            'data' => $transaksi->map(fn (Transaksi $item) => [
                'id' => $item->id,
                'no_struk' => $item->no_struk,
                'tanggal' => $item->tanggal_transaksi,
                'jenis' => $item->jenis_trans,
                'jenis_label' => $item->jenis_label,
                'nominal' => (int) $item->nominal,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $transaksi = $request->filled('transaksi_id')
            ? Transaksi::with('nasabah')->where('id_user', Auth::id())->find($request->input('transaksi_id'))
            : null;

        if ($request->filled('transaksi_id') && ! $transaksi) {
            return back()->withInput()->with('error', 'Transaksi asli tidak ditemukan atau bukan transaksi Anda.');
        }

        if ($transaksi && $transaksi->dikoreksi) {
            return back()->withInput()->with('error', 'Transaksi ini sudah pernah dikoreksi.');
        }

        if ($transaksi && KoreksiTransaksi::where('transaksi_id', $transaksi->id)->where('status', 'menunggu_approval')->exists()) {
            return back()->withInput()->with('error', 'Pengajuan koreksi untuk transaksi ini sedang menunggu persetujuan.');
        }

        $validated = $request->validate([
            // Tanpa transaksi asli => wajib pilih nasabah & nominal (uang rusak/palsu/temuan).
            'nasabah_id' => ['required', 'exists:nasabah,id'],
            'nominal' => $transaksi
                ? ['nullable', 'integer', 'min:1']
                : ['required', 'integer', 'min:1'],
            'alasan' => ['required', 'string', 'max:1000'],
        ], [
            'nasabah_id.required' => 'Nasabah wajib dipilih untuk koreksi tanpa transaksi asli.',
            'nominal.required' => 'Nominal wajib diisi untuk koreksi tanpa transaksi asli.',
            'nominal.min' => 'Nominal koreksi harus lebih dari 0.',
        ]);

        if ($transaksi && (int) $validated['nasabah_id'] !== (int) $transaksi->id_nasabah) {
            return back()->withInput()->with('error', 'Nasabah tidak sesuai dengan transaksi yang dipilih.');
        }

        if ($transaksi) {
            // Arah koreksi otomatis = kebalikan transaksi asli, namun boleh dipilih eksplisit
            // oleh teller dan diverifikasi tetap berlawanan agar konsisten sebagai pembalik.
            $jenisTersirat = $transaksi->jenis_trans === Transaksi::JENIS_SETORAN
                ? Transaksi::JENIS_KOREKSI_PENARIKAN
                : Transaksi::JENIS_KOREKSI_SETORAN;

            $jenis = $request->input('jenis') ?: $jenisTersirat;

            if ($jenis !== $jenisTersirat) {
                return back()->withInput()->with('error', 'Jenis koreksi harus berlawanan dengan jenis transaksi asli (sebagai pembalik).');
            }

            $nasabah = $transaksi->nasabah;
            $nominal = $validated['nominal'] ?? $transaksi->nominal;
        } else {
            $jenis = $request->validate(['jenis' => ['required', 'string', 'in:'.Transaksi::JENIS_KOREKSI_SETORAN.','.Transaksi::JENIS_KOREKSI_PENARIKAN]])['jenis'];
            $nasabah = Nasabah::findOrFail($validated['nasabah_id']);
            $nominal = $validated['nominal'];
        }

        $koreksi = KoreksiTransaksi::create([
            'transaksi_id' => $transaksi?->id,
            'jenis' => $jenis,
            'id_nasabah' => $nasabah->id,
            'id_user' => Auth::id(),
            'nominal' => $nominal,
            'alasan' => $validated['alasan'],
            'status' => 'menunggu_approval',
        ]);

        $perihal = $transaksi
            ? "transaksi {$transaksi->no_struk} sebesar Rp ".number_format((int) $transaksi->nominal, 0, ',', '.')
            : "koreksi tanpa transaksi asli atas nama {$nasabah->nama_siswa} sebesar Rp ".number_format($nominal, 0, ',', '.');

        ActivityLog::catat(Auth::id(), 'ajukan_koreksi', 'Pengajuan koreksi '.$perihal.' ('.($koreksi->jenis_label ?? $jenis).').');

        $supervisors = User::where('role', User::ROLE_SUPERVISOR)
            ->where('status', 'active')
            ->get();

        foreach ($supervisors as $spv) {
            UserNotification::kirim(
                $spv,
                UserNotification::TIPE_INFO,
                'Koreksi Menunggu Persetujuan',
                "Pengajuan koreksi {$perihal} oleh ".auth()->user()->nama_petugas.' menunggu persetujuan Supervisor.',
                route('supervisor.koreksi.index'),
            );
        }

        return redirect()->route('teller.koreksi.index')
            ->with('success', 'Pengajuan koreksi berhasil dikirim dan menunggu persetujuan Supervisor.');
    }
}
