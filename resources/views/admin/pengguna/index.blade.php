@extends('layouts.app')

@section('title', 'Manajemen Pengguna & Nasabah')
@section('page-title', 'Manajemen Pengguna & Nasabah')
@section('page-subtitle', 'Kelola petugas, data nasabah terdaftar, serta import data massal CSV dalam satu tampilan terpadu')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate pengguna-management-page">
        <div class="page-header">
            <div>
                <h1>Manajemen Pengguna &amp; Nasabah</h1>
                <div class="page-header-sub">Kelola petugas, data nasabah terdaftar, serta import data massal CSV dalam satu tampilan terpadu</div>
            </div>
            <div class="d-flex gap-3 small"><span>Total: <strong>{{ number_format($totalUsers, 0, ',', '.') }}</strong></span><span>Aktif: <strong>{{ number_format($activeUsers, 0, ',', '.') }}</strong></span><span>Tahun Ajaran: <strong>2026/2027</strong></span></div>
        </div>

        <nav class="pengguna-section-tabs" aria-label="Manajemen pengguna dan nasabah">
            <a class="pengguna-section-tab {{ $activeTab === 'list' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'list']) }}">
                <span class="pengguna-tab-icon"><i class="fa-solid fa-users"></i></span>
                <span class="pengguna-tab-label">Daftar Pengguna &amp; Nasabah</span>
                <span class="pengguna-tab-count">{{ number_format($totalUsers, 0, ',', '.') }}</span>
            </a>
            <a class="pengguna-section-tab {{ $activeTab === 'import' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'import']) }}">
                <span class="pengguna-tab-icon"><i class="fa-solid fa-file-import"></i></span>
                <span class="pengguna-tab-label">Import Data Nasabah (CSV)</span>
                <span class="pengguna-tab-count import-batch">CSV</span>
            </a>
            <a class="pengguna-section-tab {{ $activeTab === 'history' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'history']) }}">
                <span class="pengguna-tab-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
                <span class="pengguna-tab-label">Riwayat &amp; Log Import</span>
            </a>
            <a class="pengguna-section-tab {{ $activeTab === 'archived' ? 'active' : '' }}" href="{{ route('admin.pengguna.index', ['tab' => 'archived']) }}">
                <span class="pengguna-tab-icon"><i class="fa-solid fa-box-archive"></i></span>
                <span class="pengguna-tab-label">Diarsipkan</span>
                <span class="pengguna-tab-count">{{ number_format($archivedCount, 0, ',', '.') }}</span>
            </a>
        </nav>

        @if ($activeTab === 'import')
            @include('admin.pengguna.partials.import')
        @elseif ($activeTab === 'history')
            @include('admin.pengguna.partials.history')
        @elseif ($activeTab === 'archived')
            @include('admin.pengguna.partials.archived')
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
                        <select name="kelas" class="form-select form-select-sm" style="width:auto;">
                            <option value="">Semua Kelas</option>
                            @foreach (\App\Models\Nasabah::KELAS_GROUPS as $jurusan => $kelasPilihan)
                                <optgroup label="{{ $jurusan }}">
                                    @foreach ($kelasPilihan as $pilihan)
                                        <option value="{{ $pilihan }}" @selected($kelas === $pilihan)>{{ $pilihan }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                    </form>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPetugas"><i class="fa-solid fa-user-plus me-1"></i>Tambah Petugas</button>
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahNasabah">
                        <i class="fa-solid fa-user-graduate me-1"></i>Tambah Nasabah
                    </button>
                </div>
            </div>
            <form id="pengguna-bulk-form" method="POST" action="{{ route('admin.pengguna.arsip-terpilih') }}">
                @csrf
            </form>
            <div id="pengguna-bulk-toolbar" class="d-none align-items-center justify-content-between gap-2 flex-wrap px-3 py-2 bg-white text-body border-bottom">
                <span><strong id="pengguna-selected-count">0</strong> terpilih</span>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="pengguna-clear-selection">Batalkan Pilihan</button>
                    <button type="button" class="btn btn-sm btn-success" data-export-format="excel">Ekspor Excel</button>
                    <button type="button" class="btn btn-sm btn-danger" data-export-format="pdf">Ekspor PDF</button>
                    <button type="submit" form="pengguna-bulk-form" class="btn btn-sm btn-warning" id="pengguna-archive-selected">Arsipkan Terpilih</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="pengguna-select-all" aria-label="Pilih semua pengguna di halaman"></th>
                            <th>#</th>
                            @if ($filter === 'nasabah' || $filter === 'all')
                                <th>No. Rekening</th>
                                <th>NIS</th>
                            @endif
                            <th>Nama</th>
                            @if ($filter !== 'nasabah')
                                <th>Username / NIS</th>
                                @if ($filter !== 'all')
                                    <th>Email</th>
                                @endif
                            @endif
                            @if ($filter === 'nasabah' || $filter === 'all')
                                <th>Kelas</th>
                                <th>Produk</th>
                            @endif
                            @if ($filter !== 'nasabah')
                                <th>Role</th>
                            @else
                                <th>Role</th>
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
                                <td><input type="checkbox" class="pengguna-row-select" value="{{ $isNasabah ? 'nasabah' : 'petugas' }}:{{ $p->id }}" data-archivable="{{ $isNasabah && ! $arsip ? '1' : '0' }}" aria-label="Pilih {{ $p->nama }}"></td>
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
                                    @if ($filter !== 'all')
                                        <td class="small">{{ $p->email ?: '—' }}</td>
                                    @endif
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
                                                'teller' => 'primary',
                                                default => 'primary',
                                            };
                                            $roleLabel = match ($rk) {
                                                'admin' => 'Admin',
                                                'supervisor' => 'Supervisor',
                                                'teller' => 'Teller',
                                                default => ucfirst($rk),
                                            };
                                        @endphp
                                        <span class="badge badge-soft-{{ $badge }}">{{ $roleLabel }}</span>
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
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit User" data-bs-toggle="modal" data-bs-target="#modalEditPetugas{{ $uid }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
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
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit Nasabah" data-bs-toggle="modal" data-bs-target="#modalEditNasabah{{ $nid }}">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
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
                                    $cols = 2;
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
            @foreach ($pengguna as $item)
                @if (($item->type ?? '') === 'petugas')
                    <div class="modal fade pengguna-edit-modal" id="modalEditPetugas{{ $item->user_id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header px-4">
                                    <div>
                                        <h5 class="modal-title fw-bold">Edit Petugas</h5>
                                        <div class="text-muted small">Ubah data akun petugas</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.users.update', $item->user_id) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="return_to_pengguna" value="1">
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                    <input type="hidden" name="kelas" value="{{ $kelas }}">
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <input type="hidden" name="page" value="{{ $pengguna->currentPage() }}">
                                    <input type="hidden" name="per_page" value="{{ $pengguna->perPage() }}">
                                    <div class="modal-body px-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nama Petugas <span class="text-danger">*</span></label>
                                            <input type="text" name="nama_petugas" value="{{ $item->nama }}" class="form-control" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                                                <input type="text" name="username" value="{{ $item->username }}" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                                <input type="email" name="email" value="{{ $item->email }}" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Password <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                            <input type="password" name="password" class="form-control">
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Role</label>
                                                <select name="role" class="form-select" required>
                                                    @foreach (['admin' => 'Administrator', 'teller' => 'Teller', 'supervisor' => 'Supervisor'] as $role => $label)
                                                        <option value="{{ $role }}" @selected($item->role_key === $role)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="active" @selected($item->user_status === 'active')>Aktif</option>
                                                    <option value="inactive" @selected($item->user_status === 'inactive')>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer px-4">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Perbarui Petugas</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @elseif (($item->type ?? '') === 'nasabah')
                    <div class="modal fade pengguna-edit-modal" id="modalEditNasabah{{ $item->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header px-4">
                                    <div>
                                        <h5 class="modal-title fw-bold">Edit Nasabah</h5>
                                        <div class="text-muted small">Ubah data rekening siswa</div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.nasabah.update', $item->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="return_to_pengguna" value="1">
                                    <input type="hidden" name="filter" value="{{ $filter }}">
                                    <input type="hidden" name="kelas" value="{{ $kelas }}">
                                    <input type="hidden" name="search" value="{{ $search }}">
                                    <input type="hidden" name="page" value="{{ $pengguna->currentPage() }}">
                                    <input type="hidden" name="per_page" value="{{ $pengguna->perPage() }}">
                                    <div class="modal-body px-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nama Lengkap</label>
                                            <input type="text" name="nama_siswa" value="{{ $item->nama }}" class="form-control" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">NIS</label>
                                                <input type="text" name="nis" value="{{ $item->nis }}" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Kelas</label>
                                                <select name="kelas" class="form-select" required>
                                                    @foreach (\App\Models\Nasabah::KELAS as $kelasPilihan)
                                                        <option value="{{ $kelasPilihan }}" @selected($item->kelas === $kelasPilihan)>{{ $kelasPilihan }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Email Nasabah</label>
                                                <input type="email" name="email" value="{{ $item->email }}" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">PIN Keamanan <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                                                <input type="password" name="pin_keamanan" maxlength="6" inputmode="numeric" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-semibold">Konfirmasi PIN</label>
                                                <input type="password" name="pin_keamanan_confirmation" maxlength="6" inputmode="numeric" class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer px-4">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Perbarui Nasabah</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
            @if ($pengguna->hasPages())
                <div class="p-2 border-top">{{ $pengguna->links() }}</div>
            @endif
        </div>
        @endif
    </div>
    @include('admin.users._form-modal')
    @include('admin.nasabah._form-modal')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.pengguna-edit-modal').forEach((modal) => {
                document.body.appendChild(modal);
            });

            const selection = Array.from(document.querySelectorAll('.pengguna-row-select'));
            const toolbar = document.getElementById('pengguna-bulk-toolbar');
            const count = document.getElementById('pengguna-selected-count');
            const selectAll = document.getElementById('pengguna-select-all');
            const form = document.getElementById('pengguna-bulk-form');
            const archiveButton = document.getElementById('pengguna-archive-selected');

            const updateSelection = () => {
                const selected = selection.filter((checkbox) => checkbox.checked);
                count.textContent = selected.length;
                toolbar.classList.toggle('d-none', selected.length === 0);
                toolbar.classList.toggle('d-flex', selected.length > 0);
                selectAll.checked = selection.length > 0 && selected.length === selection.length;
                archiveButton.disabled = !selected.some((checkbox) => checkbox.dataset.archivable === '1');
                form.querySelectorAll('input[name="ids[]"]').forEach((input) => input.remove());
                selected.filter((checkbox) => checkbox.dataset.archivable === '1').forEach((checkbox) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = checkbox.value.split(':')[1];
                    form.appendChild(input);
                });
            };

            selection.forEach((checkbox) => checkbox.addEventListener('change', updateSelection));
            selectAll.addEventListener('change', () => {
                selection.forEach((checkbox) => { checkbox.checked = selectAll.checked; });
                updateSelection();
            });
            document.getElementById('pengguna-clear-selection').addEventListener('click', () => {
                selection.forEach((checkbox) => { checkbox.checked = false; });
                updateSelection();
            });
            document.querySelectorAll('[data-export-format]').forEach((button) => button.addEventListener('click', () => {
                const selected = selection.filter((checkbox) => checkbox.checked);
                const exportForm = document.createElement('form');
                exportForm.method = 'POST';
                exportForm.action = `{{ url('/admin/pengguna/ekspor') }}/${button.dataset.exportFormat}`;
                exportForm.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">`;
                selected.forEach((checkbox) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = checkbox.value;
                    exportForm.appendChild(input);
                });
                document.body.appendChild(exportForm);
                exportForm.submit();
                exportForm.remove();
            }));
            form.addEventListener('submit', (event) => {
                if (!confirm('Arsipkan nasabah yang dipilih? Akun mereka akan dinonaktifkan.')) event.preventDefault();
            });
        });
    </script>
@endsection
