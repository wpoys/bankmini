<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = $user->userNotifications();

        $status = $request->query('status') === 'belum' ? 'belum' : 'semua';
        $q = trim((string) $request->query('q', ''));

        $notifikasi = (clone $query)
            ->when($status === 'belum', fn ($builder) => $builder->belumDibaca())
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($inner) use ($q) {
                    $inner->where('judul', 'like', "%{$q}%")
                        ->orWhere('pesan', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $data = [
            'notifikasi' => $notifikasi,
            'jumlahBelumDibaca' => (clone $query)->belumDibaca()->count(),
            'jumlahSudahDibaca' => (clone $query)->sudahDibaca()->count(),
            'jumlahTotal' => (clone $query)->count(),
            'status' => $status,
            'q' => $q,
        ];

        return view('notifikasi.index', $data);
    }

    public function baca(UserNotification $notifikasi): RedirectResponse
    {
        $this->pastikanMilikUser($notifikasi);

        $notifikasi->tandaiDibaca();

        if ($notifikasi->url) {
            return redirect()->to($notifikasi->url);
        }

        return back();
    }

    public function bacaSemua(): RedirectResponse
    {
        Auth::user()
            ->userNotifications()
            ->belumDibaca()
            ->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }

    public function hapus(UserNotification $notifikasi): RedirectResponse
    {
        $this->pastikanMilikUser($notifikasi);

        $notifikasi->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    protected function pastikanMilikUser(UserNotification $notifikasi): void
    {
        if ($notifikasi->user_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses ke notifikasi ini.');
        }
    }
}
