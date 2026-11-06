@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Nasabah')
@section('page-title', 'Manajemen Pengguna & Nasabah')
@section('page-subtitle', 'Kelola petugas, data nasabah terdaftar, serta import data massal CSV dalam satu tampilan terpadu')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Manajemen Pengguna &amp; Nasabah</h1>
                <div class="page-header-sub">Kelola petugas, data nasabah terdaftar, serta import data massal CSV dalam satu tampilan terpadu</div>
            </div>
            <div class="d-flex gap-3 small"><span>Total: <strong>{{ number_format($totalUsers, 0, ',', '.') }}</strong></span><span>Aktif: <strong>{{ number_format($activeUsers, 0, ',', '.') }}</strong></span><span>Tahun Ajaran: <strong>2026/2027</strong></span></div>
        </div>

        <div class="nav nav-tabs mb-3" role="tablist" aria-label="Manajemen pengguna dan nasabah">
            <a class="nav-link {{ $activeTab === 'list' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'list']) }}"><i class="fa-solid fa-users me-1"></i>Daftar Pengguna &amp; Nasabah</a>
            <a class="nav-link {{ $activeTab === 'import' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'import']) }}"><i class="fa-solid fa-file-import me-1"></i>Import Data Nasabah (CSV)</a>
            <a class="nav-link {{ $activeTab === 'history' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'history']) }}"><i class="fa-solid fa-clock-rotate-left me-1"></i>Riwayat &amp; Log Import</a>
        </div>

        @if ($activeTab === 'import')
            @include('admin.pengguna.partials.import')
        @elseif ($activeTab === 'history')
            @include('admin.pengguna.partials.history')
        @else
        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar pengguna terintegrasi</div>
                <div class="table-toolbar">
                    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="Cari nama / username / NIS / no. rekening">
                        </div>
                        <select name="filter" class="form-select form-select-sm" style="width:auto;">
                            <option value="all" @selected($filter === 'all')>Semua Role</option>
                            <option value="admin" @selected($filter === 'admin')>Admin</option>
                            <option value="supervisor" @selected($filter === 'supervisor')>Supervisor</option>
                            <option value="teller" @selected($filter === 'teller')>Teller</option>
                            <option value="nasabah" @selected($filter === 'nasabah')>Nasabah</option>
                        </select>
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                    </form>
                    <a href="{{ route('admin.users.index', ['modal' => 'petugas']) }}" class="btn btn-success btn-sm"><i class="fa-solid fa-user-plus me-1"></i>Tambah Petugas</a>
                    <a href="{{ route('admin.nasabah.index', ['modal' => 'nasabah']) }}" class="btn btn-outline-success btn-sm">
                        <i class="fa-solid fa-user-graduate me-1"></i>Tambah Nasabah
                    </a>
                    <a href="{{ route('admin.pengguna.index', ['tab' => 'import']) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-file-import me-1"></i>Import CSV
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>#</th>
                            @if ($filter === 'nasabah' || $filter === 'all')
                                <th>No. Rekening</th>
                                <th>NIS</th>
                            @endif
                            <th>Nama</th>
                            @if ($filter !== 'nasabah')
                                <th>Username / NIS</th>
                            @endif
                            @if ($filter === 'nasabah' || $filter === 'all')
                                <th>Kelas</th>
                                <th>Produk</th>
                            @endif
                            @if ($filter !== 'nasabah')
                                <th>Role</th>
                            @else
                                <th>Jenis Akun</th>
                            @endif
                            @if ($filter === 'nasabah' || $filter === 'all')
                                <th class="text-end">Saldo</th>
                            @endif
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengguna as $i => $p)
                            @php
                                $isPetugas = ($p->type ?? '') === 'petugas';
                                $isNasabah = ($p->type ?? '') === 'nasabah';
                                $userStatus = strtolower($p->user_status ?? 'active');
                                $arsip = (int) ($p->arsip ?? 0) === 1;
                                $statusText = $isNasabah && $arsip ? 'Arsip' : ucfirst($userStatus);
                                $statusClass = $isNasabah && $arsip ? 'gray' : ($userStatus === 'active' ? 'green' : 'red');
                                $saldoVal = (int) ($p->saldo ?? 0);
                                $confirmHapus = $saldoVal > 0
                                    ? 'PERINGATAN: Nasabah ini masih memiliki saldo sebesar Rp '.number_format($saldoVal, 0, ',', '.').'.'."\n\n".'Jika Anda melanjutkan penghapusan, saldo yang tersedia akan ikut hilang beserta akun user dan seluruh data terkait.'."\n\n".'Tindakan ini TIDAK DAPAT DIBATALKAN!'."\n\n".'Pilih Batal untuk membatalkan, atau Ya, Lanjutkan untuk menghapus permanen.'
                                    : 'HAPUS PERMANEN nasabah dan akun user beserta seluruh data terkait?'."\n\n".'Tindakan ini TIDAK DAPAT DIBATALKAN!'."\n\n".'Pilih Batal untuk membatalkan, atau Ya, Lanjutkan untuk melanjutkan.';
                            @endphp
                            <tr
                                data-search="{{ $p->nama }} {{ $p->username }} {{ $p->nis }} {{ $p->no_rekening }} {{ $p->role_label }}">
                                <td class="small text-muted">{{ $pengguna->firstItem() + $i }}</td>

                                @if ($filter === 'nasabah' || $filter === 'all')
                                    <td class="fw-semibold small"><code>{{ $p->no_rekening ?: '—' }}</code></td>
                                    <td class="small">{{ $p->nis ?: '—' }}</td>
                                @endif

                                <td class="fw-semibold small">
                                    {{ $p->nama }}
                                    @if ($isNasabah && $arsip)
                                        <span class="badge badge-soft-secondary ms-1">Alumni</span>
                                    @endif
                                </td>

                                @if ($filter !== 'nasabah')
                                    <td class="small"><code>{{ $p->username ?: $p->nis }}</code></td>
                                @endif

                                @if ($filter === 'nasabah' || $filter === 'all')
                                    <td class="small">{{ $p->kelas ?: '—' }}</td>
                                    <td class="small text-capitalize">{{ $p->produk ?: '-' }}</td>
                                @endif

                                @if ($filter !== 'nasabah')
                                    <td>
                                        @php
                                            $rk = strtolower($p->role_key ?? '');
                                            $badge = match ($rk) {
                                                'admin' => 'danger',
                                                'supervisor' => 'warning',
                                                'teller' => 'info',
                                                default => 'primary',
                                            };
                                        @endphp
                                        <span class="badge badge-soft-{{ $badge }}">{{ $p->role_label }}</span>
                                    </td>
                                @elseif ($filter === 'nasabah')
                                    <td><span class="badge badge-soft-success">Nasabah</span></td>
                                @endif

                                @if ($filter === 'nasabah' || $filter === 'all')
                                    <td class="num fw-semibold small text-end">{{ $isNasabah ? 'Rp '.number_format($saldoVal, 0, ',', '.') : '—' }}</td>
                                @endif

                                <td><span class="status-badge {{ $statusClass }}">{{ $statusText }}</span></td>

                                <td class="text-end">
                                    @if ($isPetugas)
                                        @php $uid = (int) $p->user_id; @endphp
                                        <a href="{{ route('admin.users.edit', $uid) }}"
                                            class="btn btn-sm btn-outline-primary" title="Edit User">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        @if ($uid !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $uid) }}" method="POST"
                                                class="d-inline" data-confirm="Hapus user {{ $p->username }}?"
                                                data-confirm-title="Hapus User">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus User">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @elseif ($isNasabah)
                                        @php $nid = (int) $p->id; @endphp
                                        <a href="{{ route('admin.nasabah.edit', $nid) }}"
                                            class="btn btn-sm btn-outline-primary" title="Edit Nasabah">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        @if ($arsip)
                                            <form action="{{ route('admin.nasabah.restore', $nid) }}" method="POST"
                                                class="d-inline"
                                                data-confirm="Kembalikan nasabah {{ $p->nama }} dari arsip?"
                                                data-confirm-title="Restore Nasabah">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning" title="Kembalikan dari arsip">
                                                    <i class="fa-solid fa-rotate-left"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.nasabah.destroy', $nid) }}" method="POST"
                                                class="d-inline" data-confirm="{{ $confirmHapus }}"
                                                data-confirm-title="Peringatan - Hapus Permanen">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Hapus permanen">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.nasabah.arsip', $nid) }}" method="POST"
                                                class="d-inline"
                                                data-confirm="Arsipkan nasabah {{ $p->nama }} sebagai alumni? Akun akan dinonaktifkan. Setelah diarsipkan, nasabah bisa dihapus permanen."
                                                data-confirm-title="Arsipkan Nasabah">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary" title="Arsipkan (alumni)">
                                                    <i class="fa-solid fa-box-archive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                @php
                                    $cols = 1;
                                    if ($filter === 'nasabah' || $filter === 'all') {
                                        $cols += 3;
                                    }
                                    $cols++;
                                    if ($filter === 'nasabah' || $filter === 'all') {
                                        $cols += 2;
                                    } else {
                                        $cols++;
                                    }
                                    if ($filter === 'nasabah' || $filter === 'all') {
                                        $cols++;
                                    }
                                    $cols += 2;
                                @endphp
                                <td colspan="{{ $cols }}">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-users"></i></div>
                                        <div class="empty-title">Tidak ada data pengguna</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pengguna->hasPages())
                <div class="p-2 border-top">{{ $pengguna->links() }}</div>
            @endif
        </div>
        @endif
    </div>
@endsection
