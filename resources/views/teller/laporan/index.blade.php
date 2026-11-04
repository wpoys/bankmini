@extends('layouts.app')

@section('title', 'Laporan Harian')
@section('page-title', 'Laporan Harian')
@section('page-subtitle', 'Kelola laporan harian transaksi Anda')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Riwayat Laporan Kas</h1>
                <div class="page-header-sub">Semua laporan rekonsiliasi loket Anda</div>
            </div>
            <div class="header-actions">
                <a href="{{ route('teller.laporan.create') }}" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus me-1"></i>Buat Laporan Hari Ini
                </a>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Riwayat Laporan</div>
                <div class="table-toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari tanggal / status...">
                    </div>
                    <select class="form-select form-select-sm d-inline-block" style="width:auto;" data-table-filter>
                        <option value="">Semua Status</option>
                        <option value="draft">Draft</option>
                        <option value="menunggu_approval">Menunggu</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Teller</th>
                            <th class="text-end">Setoran</th>
                            <th class="text-end">Penarikan</th>
                            <th class="text-end">Saldo Akhir Sistem</th>
                            <th>Selisih</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporan as $l)
                            <tr data-search="{{ $l->tanggal->format('d M Y') }} {{ $l->teller?->nama_petugas ?? '' }}" data-status="{{ $l->status }}">
                                <td class="fw-semibold small">{{ $l->tanggal->format('d M Y') }}</td>
                                <td class="small">{{ $l->teller?->nama_petugas ?? '-' }}</td>
                                <td class="num small text-end">Rp {{ number_format($l->total_setoran, 0, ',', '.') }}</td>
                                <td class="num small text-end">Rp {{ number_format($l->total_penarikan, 0, ',', '.') }}</td>
                                <td class="num small text-end">Rp {{ number_format($l->saldo_akhir_sistem, 0, ',', '.') }}</td>
                                <td>
                                    @if ($l->selisih === null)
                                        <span class="text-muted small">-</span>
                                    @elseif ($l->selisih == 0)
                                        <span class="status-badge green">Selaras</span>
                                    @else
                                        <span class="status-badge red">Selisih {{ $l->selisih }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $c2 = match ($l->status) {
                                            'draft' => 'gray',
                                            'menunggu_approval' => 'orange',
                                            'pending' => 'blue',
                                            'disetujui' => 'green',
                                            'ditolak' => 'red',
                                            default => 'gray',
                                        };
                                        $t2 = match ($l->status) {
                                            'draft' => 'Draft',
                                            'menunggu_approval' => 'Menunggu',
                                            'pending' => 'Ditahan (Pending)',
                                            'disetujui' => 'Disetujui',
                                            'ditolak' => 'Ditolak',
                                            default => $l->status_label,
                                        };
                                    @endphp
                                    <span class="status-badge {{ $c2 }}">{{ $t2 }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('teller.laporan.show', $l->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-eye me-1"></i>Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-file-lines"></i></div>
                                        <div class="empty-title">Belum ada laporan</div>
                                        <div class="empty-desc">Buat laporan kas harian setelah melakukan transaksi di loket.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($laporan->hasPages())
                <div class="p-2 border-top">{{ $laporan->links() }}</div>
            @endif
        </div>
    </div>
@endsection
