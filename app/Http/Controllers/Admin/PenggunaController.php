<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nasabah;
use App\Models\NasabahImportBatch;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $activeTab = in_array($request->get('tab'), ['list', 'import', 'history'], true) ? $request->get('tab') : 'list';
        $batches = NasabahImportBatch::with('importer')->latest()->paginate(10, ['*'], 'imports_page')->withQueryString();
        $totalUsers = User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_TELLER])->count() + Nasabah::count();
        $activeUsers = User::where('status', 'active')->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERVISOR, User::ROLE_TELLER])->count() + Nasabah::where('arsip', false)->count();
        $filter = strtolower(trim($request->get('filter', 'all')));
        $search = trim($request->get('search'));

        $allowed = ['all', 'admin', 'supervisor', 'teller', 'nasabah'];
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
                ->when($filter !== 'all', fn (Builder $q) => $q)
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

        return view('admin.pengguna.index', compact('filter', 'search', 'pengguna', 'activeTab', 'batches', 'totalUsers', 'activeUsers'));
    }
}
