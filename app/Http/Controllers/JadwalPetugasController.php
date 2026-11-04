<?php

namespace App\Http\Controllers;

use App\Models\JadwalPetugas;
use App\Models\User;
use App\Support\JadwalPetugasService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Jadwal Petugas untuk seluruh role non-nasabah: Admin & Supervisor melihat
 * semua (Supervisor read-only), Teller mengelola jadwal miliknya sendiri.
 */
class JadwalPetugasController extends Controller
{
    protected function prefix(): string
    {
        return match (true) {
            Auth::user()->isAdmin() => 'admin',
            Auth::user()->isTeller() => 'teller',
            default => 'supervisor',
        };
    }

    protected function daftarRoleYangDikelola(User $aktor): array
    {
        if ($aktor->isAdmin()) {
            return [User::ROLE_ADMIN, User::ROLE_TELLER, User::ROLE_SUPERVISOR];
        }

        return [$aktor->role];
    }

    protected function userBolehKelola(JadwalPetugas $jadwal): bool
    {
        $aktor = Auth::user();

        if ($aktor->isAdmin()) {
            return true;
        }

        if ($aktor->isTeller()) {
            return $jadwal->user_id === $aktor->id;
        }

        return $jadwal->user_id === $aktor->id;
    }

    public function index(Request $request): View
    {
        $aktor = Auth::user();
        $prefix = $this->prefix();

        $query = JadwalPetugas::with(['user', 'pembuat'])
            ->when(!$aktor->isAdmin() && !$aktor->isSupervisor(), fn($q) => $q->where('user_id', $aktor->id))
            ->when($request->tanggal, fn($q, $tanggal) => $q->whereDate('tanggal', $tanggal))
            ->when($request->hari, fn($q, $hari) => $q->where('hari', $hari))
            ->when($request->status, fn($q, $status) => $q->where('status', $status))
            ->orderBy('tanggal', 'desc')
            ->orderBy('jam_mulai');

        $jadwal = $query->paginate(15)->withQueryString();

        return view('jadwal.index', [
            'jadwal' => $jadwal,
            'prefix' => $prefix,
            'canManage' => !$aktor->isSupervisor(),
            'hariOptions' => ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'],
            'statusOptions' => [
                JadwalPetugas::STATUS_DIJADWALKAN,
                JadwalPetugas::STATUS_BERTUGAS,
                JadwalPetugas::STATUS_SELESAI,
                JadwalPetugas::STATUS_DIBATALKAN,
            ],
        ]);
    }

    public function create(): View
    {
        $aktor = Auth::user();

        abort_unless(!$aktor->isSupervisor() && !$aktor->isNasabah(), 403);

        return view('jadwal.create', $this->dataForm($aktor));
    }

    public function store(Request $request): RedirectResponse
    {
        $aktor = Auth::user();

        abort_unless(!$aktor->isSupervisor() && !$aktor->isNasabah(), 403);

        try {
            $jadwal = JadwalPetugasService::simpan($aktor, $request->all());

            return redirect()->route($this->prefix() . '.jadwal.index')
                ->with('success', 'Jadwal ' . $jadwal->nama_petugas . ' tanggal ' . Carbon::parse((string) $jadwal->tanggal)->format('d M Y') . ' berhasil disimpan.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan jadwal: ' . $e->getMessage());
        }
    }

    public function edit(JadwalPetugas $jadwal): View
    {
        abort_unless($this->userBolehKelola($jadwal), 403);

        return view('jadwal.edit', [
            'jadwal' => $jadwal,
            ...$this->dataForm(Auth::user()),
        ]);
    }

    public function update(Request $request, JadwalPetugas $jadwal): RedirectResponse
    {
        abort_unless($this->userBolehKelola($jadwal), 403);

        try {
            $jadwal = JadwalPetugasService::simpan(Auth::user(), $request->all(), $jadwal);

            return redirect()->route($this->prefix() . '.jadwal.index')
                ->with('success', 'Jadwal ' . $jadwal->nama_petugas . ' tanggal ' . Carbon::parse((string) $jadwal->tanggal)->format('d M Y') . ' diperbarui.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal memperbarui jadwal: ' . $e->getMessage());
        }
    }

    public function batalkan(JadwalPetugas $jadwal): RedirectResponse
    {
        abort_unless($this->userBolehKelola($jadwal), 403);

        if ($jadwal->status !== JadwalPetugas::STATUS_DIJADWALKAN) {
            return back()->with('error', 'Hanya jadwal berstatus Dijadwalkan yang dapat dibatalkan.');
        }

        $jadwal->update(['status' => JadwalPetugas::STATUS_DIBATALKAN]);

        return back()->with('success', 'Jadwal ' . $jadwal->nama_petugas . ' dibatalkan.');
    }

    protected function dataForm(User $aktor): array
    {
        return [
            'prefix' => $this->prefix(),
            'aktor' => $aktor,
            'roleOptions' => $this->daftarRoleYangDikelola($aktor),
            'usersByRole' => User::whereIn('role', $this->daftarRoleYangDikelola($aktor))
                ->whereIn('status', ['active'])
                ->orderBy('nama_petugas')
                ->get()
                ->groupBy('role'),
        ];
    }
}
