<?php

namespace App\Http\Controllers;

use App\Models\PermintaanResetPin;
use App\Support\PermintaanPinService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Persetujuan pengajuan perubahan PIN nasabah.
 *
 * Admin dan Supervisor memiliki HAK PERSETUJUAN YANG SAMA: cukup salah satu
 * yang menyetujui, tidak perlu keduanya. Menu ini dipakai keduanya.
 *
 * Keamanan: halaman ini TIDAK PERNAH menampilkan PIN baru maupun PIN lama.
 * Yang tampil hanya nama, nomor rekening, kelas, waktu, dan status.
 */
class PermintaanPinController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $permintaan = PermintaanResetPin::with(['nasabah.user', 'pemroses'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByRaw('CASE WHEN status = "pending" THEN 0 ELSE 1 END')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pending = (int) PermintaanResetPin::where('status', PermintaanResetPin::STATUS_PENDING)->count();

        return view('permintaan-pin.index', compact('permintaan', 'pending', 'status'));
    }

    public function setujui(Request $request, PermintaanResetPin $permintaan): RedirectResponse
    {
        $request->validate([
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            PermintaanPinService::setujui($permintaan, $request->user(), $request->input('catatan'));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan perubahan PIN disetujui. PIN baru sudah otomatis berlaku untuk nasabah.');
    }

    public function tolak(Request $request, PermintaanResetPin $permintaan): RedirectResponse
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'max:500'],
        ], [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi agar nasabah mengetahui penyebabnya.',
        ]);

        try {
            PermintaanPinService::tolak($permintaan, $request->user(), $validated['alasan_penolakan']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pengajuan perubahan PIN ditolak. PIN lama nasabah tetap digunakan.');
    }
}
