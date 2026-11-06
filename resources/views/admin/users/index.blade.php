@extends('layouts.app')

@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')
@section('page-subtitle', 'Kelola akun petugas dan nasabah')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Daftar User</h1>
                <div class="page-header-sub">Kelola akun petugas dan nasabah</div>
            </div>
        </div>

        <div class="nav nav-tabs mb-3" role="tablist" aria-label="Manajemen pengguna">
            <a class="nav-link active" href="{{ route('admin.users.index') }}" aria-current="page">
                <i class="fa-solid fa-users me-1"></i>Manajemen User
            </a>
            <a class="nav-link" href="{{ route('admin.nasabah.import') }}">
                <i class="fa-solid fa-file-import me-1"></i>Impor Nasabah
            </a>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar akun pengguna</div>
                <div class="table-toolbar">
                    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Cari username / nama">
                        </div>
                        <select name="role" class="form-select form-select-sm" style="width:auto">
                            <option value="">Semua Role</option>
                            @foreach (\App\Models\User::ROLE_OPTION as $val => $label)
                                <option value="{{ $val }}" @selected(request('role') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                    </form>
                    <a href="{{ route('admin.users.index', ['modal' => 'petugas']) }}" class="btn btn-success btn-sm">
                        <i class="fa-solid fa-plus me-1"></i>Tambah Petugas</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Petugas</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $i => $u)
                            <tr data-search="{{ $u->username }} {{ $u->nama_petugas }}">
                                <td class="small text-muted">{{ $users->firstItem() + $i }}</td>
                                <td class="fw-semibold small">{{ $u->nama_petugas }}</td>
                                <td class="small"><code>{{ $u->username }}</code></td>
                                <td>
                                    <span class="badge badge-soft-{{ $u->role === 'admin' ? 'danger' : ($u->role === 'teller' ? 'info' : ($u->role === 'supervisor' ? 'warning' : ($u->role === 'cs' ? 'secondary' : 'success'))) }}">
                                        {{ $u->role_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge {{ $u->status === 'active' ? 'green' : 'red' }}">{{ ucfirst($u->status) }}</span>
                                </td>
                                <td class="small text-muted">{{ $u->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-pen"></i></a>
                                    @if ($u->id !== auth()->id())
                                        <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" class="d-inline" data-confirm="Hapus user {{ $u->username }}?" data-confirm-title="Hapus User">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                        <div class="empty-title">Tidak ada user</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="p-2 border-top">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
    @include('admin.users._form-modal')
@endsection

@push('scripts')
    @if (request('modal') === 'petugas' || ($errors->any() && old('_form') === 'petugas'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahPetugas')).show();
            });
        </script>
    @endif
@endpush
