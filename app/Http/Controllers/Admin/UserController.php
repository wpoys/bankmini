<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    protected function roleRules(): array
    {
        return [
            'required',
            Rule::in([User::ROLE_ADMIN, User::ROLE_TELLER, User::ROLE_SUPERVISOR]),
        ];
    }

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->search, fn ($q) => $q
                ->where('username', 'like', "%{$request->search}%")
                ->orWhere('nama_petugas', 'like', "%{$request->search}%"))
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.users.index', ['modal' => 'petugas']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'nama_petugas' => ['required', 'string', 'max:255'],
            'role' => $this->roleRules(),
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'nama_petugas' => $validated['nama_petugas'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        ActivityLog::catat(auth()->id(), 'tambah_user', "User {$validated['username']} ditambahkan.");

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'nama_petugas' => ['required', 'string', 'max:255'],
            'role' => $this->roleRules(),
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user->fill([
            'username' => $validated['username'],
            'nama_petugas' => $validated['nama_petugas'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        ActivityLog::catat(auth()->id(), 'ubah_user', "User {$user->username} diperbarui.");

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        if ($user->isNasabah() && $user->nasabah()->exists()) {
            if ($user->nasabah->transaksi()->exists()) {
                return back()->with('error', 'Nasabah tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
            }

            return $this->hapusNasabahUser($user);
        }

        if ($user->transaksi()->exists() || $user->laporanHarian()->exists()) {
            return back()->with('error', 'User tidak dapat dihapus karena telah mencatat transaksi/laporan. Nonaktifkan status akunnya sebagai gantinya.');
        }

        try {
            $user->delete();

            ActivityLog::catat(auth()->id(), 'hapus_user', "User {$user->username} dihapus.");

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'User tidak dapat dihapus karena masih memiliki data terkait.');
        }
    }

    protected function hapusNasabahUser(User $user): RedirectResponse
    {
        try {
            DB::transaction(function () use ($user) {
                $user->nasabah()->delete();
                $user->delete();
            });

            ActivityLog::catat(auth()->id(), 'hapus_user', "User {$user->username} dihapus.");

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil dihapus.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Nasabah tidak dapat dihapus karena masih memiliki data terkait (transaksi/laporan).');
        }
    }
}
