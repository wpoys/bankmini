@extends('layouts.app')

@section('title', 'Manajemen Nasabah')
@section('page-title', 'Manajemen Nasabah')
@section('page-subtitle', 'Kelola data rekening siswa')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Daftar Nasabah</h1>
                <div class="page-header-sub">Kelola data rekening siswa</div>
            </div>
            <div class="header-actions">
                <form class="d-flex gap-2 align-items-center" method="GET">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="form-control form-control-sm" placeholder="Cari nama / rekening / NIS">
                    </div>
                    <select name="kelas" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Semua Kelas</option>
                        @foreach (\App\Models\Nasabah::KELAS_GROUPS as $jurusan => $kelasPilihan)
                            <optgroup label="{{ $jurusan }}">
                                @foreach ($kelasPilihan as $kelas)
                                    <option value="{{ $kelas }}" @selected(request('kelas') === $kelas)>{{ $kelas }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <select name="arsip" class="form-select form-select-sm" style="width:auto;">
                        <option value="">Semua Status</option>
                        <option value="aktif" @selected(request('arsip') === 'aktif')>Aktif</option>
                        <option value="arsip" @selected(request('arsip') === 'arsip')>Arsip (Alumni)</option>
                    </select>
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                </form>
                <a href="{{ route('admin.nasabah.index', ['modal' => 'nasabah']) }}" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Tambah Nasabah</a>
                <a href="{{ route('admin.nasabah.import') }}" class="btn btn-outline-primary btn-sm"><i
                        class="fa-solid fa-file-import me-1"></i>Import CSV</a>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar nasabah</div>
                <div class="table-toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control form-control-sm" data-table-filter
                            placeholder="Filter di tabel...">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>No. Rekening</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th class="text-end">Saldo</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($nasabah as $i => $n)
                            <tr class="{{ $n->isArsip() ? 'table-secondary' : '' }}"
                                data-search="{{ $n->no_rekening }} {{ $n->nis }} {{ $n->nama_siswa }}">
                                <td class="small text-muted">{{ $nasabah->firstItem() + $i }}</td>
                                <td class="fw-semibold small"><code>{{ $n->no_rekening }}</code></td>
                                <td class="small">{{ $n->nis }}</td>
                                <td class="fw-semibold small">
                                    {{ $n->nama_siswa }}
                                    @if ($n->isArsip())
                                        <span class="badge badge-soft-secondary ms-1">Alumni</span>
                                    @endif
                                </td>
                                <td class="small">{{ $n->kelas }}</td>
                                <td class="num fw-semibold small text-end">Rp {{ number_format($n->saldo, 0, ',', '.') }}</td>
                                <td>
                                    @if ($n->isArsip())
                                        <span class="status-badge gray">Arsip</span>
                                    @else
                                        <span class="status-badge {{ $n->user?->status === 'active' ? 'green' : 'red' }}">
                                            {{ ucfirst($n->user?->status ?? 'unknown') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.nasabah.edit', $n->id) }}" class="btn btn-sm btn-outline-primary"
                                        title="Edit"><i class="fa-solid fa-pen"></i></a>
                                    @if ($n->isArsip())
                                        <form action="{{ route('admin.nasabah.restore', $n->id) }}" method="POST" class="d-inline"
                                            data-confirm="Kembalikan nasabah {{ $n->nama_siswa }} dari arsip?"
                                            data-confirm-title="Restore Nasabah">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning" title="Kembalikan dari arsip"><i
                                                    class="fa-solid fa-rotate-left"></i></button>
                                        </form>
                                        <form action="{{ route('admin.nasabah.destroy', $n->id) }}" method="POST" class="d-inline"
                                            data-confirm="{{ (int) $n->saldo > 0 ? 'PERINGATAN: Saldo nasabah masih Rp ' . number_format($n->saldo, 0, ',', '.') . '.\n\nMenghapus nasabah secara permanen akan menghapus saldo tersebut beserta akun user dan seluruh data terkait.\n\nTindakan ini TIDAK DAPAT DIBATALKAN!\n\nPilih Batal untuk membatalkan, atau Ya, Lanjutkan untuk menghapus permanen.' : 'HAPUS PERMANEN nasabah dan akun user beserta seluruh riwayat transaksi?\n\nTindakan ini TIDAK DAPAT DIBATALKAN!\n\nPilih Batal untuk membatalkan, atau Ya, Lanjutkan untuk melanjutkan.' }}"
                                            data-confirm-title="Peringatan - Hapus Permanen">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" title="Hapus permanen"><i
                                                    class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.nasabah.arsip', $n->id) }}" method="POST" class="d-inline"
                                            data-confirm="Arsipkan nasabah {{ $n->nama_siswa }} sebagai alumni? Akun akan dinonaktifkan."
                                            data-confirm-title="Arsipkan Nasabah">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" title="Arsipkan (alumni)"><i
                                                    class="fa-solid fa-box-archive"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-user-graduate"></i></div>
                                        <div class="empty-title">Tidak ada nasabah</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($nasabah->hasPages())
                <div class="p-2 border-top">{{ $nasabah->links() }}</div>
            @endif
        </div>
    </div>
    @include('admin.nasabah._form-modal')
@endsection

@push('scripts')
    @if (request('modal') === 'nasabah' || ($errors->any() && old('_form') === 'nasabah'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalTambahNasabah')).show();
            });
        </script>
    @endif
@endpush