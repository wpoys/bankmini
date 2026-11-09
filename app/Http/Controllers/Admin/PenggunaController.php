<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\NasabahImportBatch;
use App\Models\ActivityLog;
use App\Models\User;
use App\Support\ExcelExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function arsipTerpilih(Request $request): RedirectResponse
    {
        $validated = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'distinct', 'exists:nasabah,id']]);
        $nasabah = Nasabah::whereIn('id', $validated['ids'])->where('arsip', false)->with('user')->get();

        DB::transaction(function () use ($nasabah) {
            foreach ($nasabah as $item) {
                $item->update(['arsip' => true]);
                ($item->user ?? $item->ensureUser())->update(['status' => 'inactive']);
            }
        });

        return back()->with('success', $nasabah->count().' nasabah berhasil diarsipkan.');
    }

    public function ekspor(Request $request, string $format)
    {
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);
        $validated = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['required', 'string']]);
        $items = collect($validated['ids'])->map(function ($key) {
            [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
            abort_unless(in_array($type, ['nasabah', 'petugas'], true) && ctype_digit((string) $id), 422);
            return [$type, (int) $id];
        });
        $nasabahIds = $items->where(0, 'nasabah')->pluck(1)->all();
        $petugasIds = $items->where(0, 'petugas')->pluck(1)->all();
        $rows = collect();
        if ($nasabahIds) {
            $rows = $rows->concat(Nasabah::with('user')->whereIn('id', $nasabahIds)->get()->map(fn ($item) => [
                'Nasabah', $item->no_rekening, $item->nis, $item->nama_siswa, $item->user?->username, $item->kelas,
                $item->produk, 'Nasabah', $item->saldo, $item->arsip ? 'Arsip' : ($item->user?->status ?? 'active'),
            ]));
        }
        if ($petugasIds) {
            $rows = $rows->concat(User::whereIn('id', $petugasIds)->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_TELLER])->get()->map(fn ($item) => [
                'Petugas', '', '', $item->nama_petugas ?: $item->username, $item->username, '', '', ucfirst($item->role), '', $item->status,
            ]));
        }
        abort_if($rows->isEmpty(), 422, 'Tidak ada pengguna valid untuk diekspor.');
        $headers = ['Jenis', 'No. Rekening', 'NIS', 'Nama', 'Username', 'Kelas', 'Produk', 'Role', 'Saldo', 'Status'];

        if ($format === 'excel') {
            return ExcelExport::download('pengguna-terpilih', $headers, $rows->all(), 'Pengguna');
        }

        return Pdf::loadView('admin.pengguna.pdf', compact('headers', 'rows'))->setPaper('a4', 'landscape')->download('pengguna-terpilih.pdf');
    }

    public function pulihkanTerpilih(Request $request): RedirectResponse
    {
        $validated = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'distinct', 'exists:nasabah,id']]);
        $nasabah = Nasabah::whereIn('id', $validated['ids'])->where('arsip', true)->with('user')->get();

        DB::transaction(function () use ($nasabah) {
            foreach ($nasabah as $item) {
                $item->update(['arsip' => false]);
                ($item->user ?? $item->ensureUser())->update(['status' => 'active']);
            }
        });

        return redirect()->route('admin.pengguna.index', ['tab' => 'archived'])->with('success', $nasabah->count().' nasabah berhasil dipulihkan.');
    }

    public function hapusTerpilih(Request $request): RedirectResponse
    {
        $validated = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer', 'distinct', 'exists:nasabah,id']]);
        $nasabah = Nasabah::whereIn('id', $validated['ids'])->where('arsip', true)->get();
        $removed = 0;
        $failed = 0;

        foreach ($nasabah as $item) {
            try {
                $nama = $item->nama_siswa;
                $akun = $item->user;
                DB::transaction(function () use ($item, $akun) {
                    $transaksiIds = $item->transaksi()->pluck('id');
                    if ($transaksiIds->isNotEmpty()) {
                        DB::table('koreksi_transaksi')->whereIn('transaksi_id', $transaksiIds)->update(['id_nasabah' => null]);
                        DB::table('transaksi')->whereIn('id', $transaksiIds)->update(['id_nasabah' => null]);
                    }
                    if ($akun) {
                        DB::table('transaksi')->where('id_user', $akun->id)->update(['id_user' => null]);
                        DB::table('koreksi_transaksi')->where('id_user', $akun->id)->update(['id_user' => null]);
                    }
                    DB::table('permintaan_reset_pin')->where('id_nasabah', $item->id)->delete();
                    DB::table('koreksi_transaksi')->where('id_nasabah', $item->id)->update(['id_nasabah' => null]);
                    $item->delete();
                    $akun?->delete();
                });
                ActivityLog::catat(auth()->id(), 'hapus_nasabah', "Nasabah {$nama} dihapus permanen; riwayat transaksi dipertahankan.");
                $removed++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        $message = "{$removed} nasabah berhasil dihapus; riwayat transaksi tetap dipertahankan.";
        if ($failed > 0) {
            $message .= " {$failed} gagal dihapus.";
        }

        return redirect()->route('admin.pengguna.index', ['tab' => 'archived'])->with('success', $message);
    }

    public function index(Request $request): View
    {
        $activeTab = in_array($request->get('tab'), ['list', 'import', 'history', 'archived'], true) ? $request->get('tab') : 'list';
        $batches = NasabahImportBatch::with('importer')->latest()->paginate(10, ['*'], 'imports_page')->withQueryString();
        $totalUsers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_TELLER])->count() + Nasabah::count();
        $activeUsers = User::where('status', 'active')->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_TELLER])->count() + Nasabah::where('arsip', false)->count();
        $archivedCount = Nasabah::where('arsip', true)->count();
        $archivedSearch = trim($request->get('archived_search', ''));
        $archivedClass = trim($request->get('archived_kelas', ''));
        if (! in_array($archivedClass, Nasabah::KELAS, true)) {
            $archivedClass = '';
        }
        $archivedNasabah = Nasabah::with('user')
            ->where('arsip', true)
            ->when($archivedSearch !== '', fn ($query) => $query->where(function ($sub) use ($archivedSearch) {
                $sub->where('nama_siswa', 'like', "%{$archivedSearch}%")
                    ->orWhere('nis', 'like', "%{$archivedSearch}%")
                    ->orWhere('no_rekening', 'like', "%{$archivedSearch}%");
            }))
            ->when($archivedClass !== '', fn ($query) => $query->where('kelas', $archivedClass))
            ->orderBy('nama_siswa')
            ->paginate(15, ['*'], 'archived_page')
            ->withQueryString();
        $filter = strtolower(trim($request->get('filter', 'all')));
        $kelas = trim($request->get('kelas', ''));
        $search = trim($request->get('search'));

        $allowed = ['all', 'admin', 'supervisor', 'teller', 'nasabah'];
        if (! in_array($kelas, Nasabah::KELAS, true)) {
            $kelas = '';
        } else {
            $filter = 'nasabah';
        }
        if (! in_array($filter, $allowed, true)) {
            $filter = 'all';
        }

        $petugasQuery = null;
        $nasabahQuery = null;

        if ($filter !== 'nasabah') {
            $petugasQuery = DB::table('users')
                ->selectRaw("
                    'petugas' AS type,
                    users.id AS id,
                    users.id AS user_id,
                    users.role AS role_key,
                    CASE
                        WHEN users.role = 'admin' THEN 'Admin'
                        WHEN users.role = 'supervisor' THEN 'Supervisor'
                        WHEN users.role = 'teller' THEN 'Teller'
                        ELSE CONCAT(UPPER(SUBSTRING(users.role,1,1)), LOWER(SUBSTRING(users.role,2)))
                    END AS role_label,
                    COALESCE(NULLIF(TRIM(users.nama_petugas), ''), users.username) AS nama,
                    users.username AS username,
                    users.email AS email,
                    NULL AS nis,
                    NULL AS no_rekening,
                    NULL AS kelas,
                    NULL AS produk,
                    0 AS saldo,
                    users.status AS user_status,
                    0 AS arsip,
                    users.created_at AS created_at,
                    users.updated_at AS updated_at
                ")
                ->whereIn('users.role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_TELLER])
                ->when($search !== '', function (Builder $q) use ($search) {
                    $q->where(function (Builder $sub) use ($search) {
                        $sub->where('users.username', 'like', "%{$search}%")
                            ->orWhere('users.nama_petugas', 'like', "%{$search}%");
                    });
                })
                ->when($filter !== 'all', fn (Builder $q) => $q->where('users.role', $filter));
        }

        if (in_array($filter, ['nasabah', 'all'], true)) {
            $nasabahQuery = DB::table('nasabah')
                ->leftJoin('users', 'users.id', '=', 'nasabah.user_id')
                ->selectRaw("
                    'nasabah' AS type,
                    nasabah.id AS id,
                    nasabah.user_id AS user_id,
                    'nasabah' AS role_key,
                    'Nasabah' AS role_label,
                    COALESCE(NULLIF(TRIM(nasabah.nama_siswa), ''), 'Nasabah') AS nama,
                    COALESCE(users.username, '') AS username,
                    COALESCE(users.email, '') AS email,
                    nasabah.nis AS nis,
                    nasabah.no_rekening AS no_rekening,
                    nasabah.kelas AS kelas,
                    nasabah.produk AS produk,
                    nasabah.saldo AS saldo,
                    COALESCE(users.status, 'active') AS user_status,
                    CASE WHEN nasabah.arsip = 1 THEN 1 ELSE 0 END AS arsip,
                    nasabah.created_at AS created_at,
                    nasabah.updated_at AS updated_at
                ")
                ->where('nasabah.arsip', false)
                ->when($kelas !== '', fn (Builder $q) => $q->where('nasabah.kelas', $kelas))
                ->when($search !== '', function (Builder $q) use ($search) {
                    $q->where(function (Builder $sub) use ($search) {
                        $sub->where('nasabah.nama_siswa', 'like', "%{$search}%")
                            ->orWhere('nasabah.nis', 'like', "%{$search}%")
                            ->orWhere('nasabah.no_rekening', 'like', "%{$search}%");
                    });
                });
        }

        $items = collect();
        if ($petugasQuery && $nasabahQuery) {
            $items = $petugasQuery->unionAll($nasabahQuery);
        } elseif ($petugasQuery) {
            $items = $petugasQuery;
        } elseif ($nasabahQuery) {
            $items = $nasabahQuery;
        }

        $items = $items
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0) {
            $perPage = 15;
        }

        $pengguna = $items->paginate($perPage)->withQueryString();

        return view('admin.pengguna.index', compact('filter', 'kelas', 'search', 'pengguna', 'activeTab', 'batches', 'totalUsers', 'activeUsers', 'archivedCount', 'archivedSearch', 'archivedClass', 'archivedNasabah'));
    }
}
