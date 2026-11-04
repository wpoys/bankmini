@extends('layouts.app')

@section('title', 'Dashboard Teller')
@section('page-title', 'Dashboard Teller')
@section('page-subtitle', 'Lakukan transaksi setoran & penarikan dengan cepat')

@php
    $transaksiPending = $transaksiTerbaru->where('status', 'pending')->count();
    $selisihKas = 0;
@endphp

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Dashboard Operasional</h1>
                <div class="page-header-sub">
                    Transaksi hari ini · Loket Teller — {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
            <div class="header-actions">
                <span class="status-badge green">Sistem Aktif</span>
                <div class="header-date d-xl-none"><i class="fa-solid fa-calendar-days"></i>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</div>
                <a href="{{ route('teller.transaksi.index') }}" class="quick-action"><i class="fa-solid fa-arrow-right-arrow-left"></i><span>Transaksi</span></a>
                <a href="{{ route('teller.laporan.create') }}" class="quick-action"><i class="fa-solid fa-file-lines"></i><span>Buat Laporan Kas</span></a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-xl-2"><div class="stat-card h-100"><div class="stat-top"><span class="stat-label">Setoran</span><span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-down"></i></span></div><div class="stat-value">Rp {{ number_format($totalSetoranHariIni, 0, ',', '.') }}</div><div class="stat-hint">Hari ini</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card h-100"><div class="stat-top"><span class="stat-label">Penarikan</span><span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-up"></i></span></div><div class="stat-value">Rp {{ number_format($totalPenarikanHariIni, 0, ',', '.') }}</div><div class="stat-hint">Hari ini</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card h-100"><div class="stat-top"><span class="stat-label">Total Transaksi</span><span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-receipt"></i></span></div><div class="stat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</div><div class="stat-hint">Sepanjang hari</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card h-100"><div class="stat-top"><span class="stat-label">Saldo Fisik Kas</span><span class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-vault"></i></span></div><div class="stat-value">Rp {{ number_format($saldoKas, 0, ',', '.') }}</div><div class="stat-hint">Menunggu rekonsiliasi</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card h-100"><div class="stat-top"><span class="stat-label">Pending</span><span class="stat-icon bg-warning-subtle text-warning"><i class="fa-solid fa-hourglass-half"></i></span></div><div class="stat-value">{{ $transaksiPending }}</div><div class="stat-hint">Menunggu approval</div></div></div>
            <div class="col-6 col-xl-2"><div class="stat-card h-100"><div class="stat-top"><span class="stat-label">Selisih Kas</span><span class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-scale-balanced"></i></span></div><div class="stat-value {{ $selisihKas == 0 ? 'text-success' : 'text-danger' }}">Rp {{ number_format($selisihKas, 0, ',', '.') }}</div><div class="stat-hint">Sistem vs fisik</div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-12"><div class="data-panel"><div class="panel-head"><div class="panel-title"><span class="dot"></span>Transaksi Terbaru</div><div class="table-toolbar"><div class="search-box"><i class="fa-solid fa-magnifying-glass"></i><input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah..."></div><a href="{{ route('teller.transaksi.index') }}" class="quick-action"><i class="fa-solid fa-plus"></i><span>Transaksi Baru</span></a></div></div><div class="table-responsive"><table class="table table-hover align-middle table-nowrap mb-0" data-filterable><thead><tr><th>No. Struk</th><th>Waktu</th><th>Nasabah</th><th>Jenis</th><th class="text-end">Nominal</th><th>Petugas</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>@forelse ($transaksiTerbaru as $t)<tr data-search="{{ $t->no_struk }} {{ $t->nasabah->nama_siswa ?? '' }}" data-status="{{ $t->status }}"><td><code class="small">{{ $t->no_struk }}</code></td><td class="text-muted small">{{ $t->created_at->format('H:i') }}</td><td class="fw-semibold small">{{ $t->nasabah->nama_siswa ?? '-' }}</td><td><span class="badge badge-soft-{{ in_array($t->jenis_trans, ['setoran', 'koreksi_setoran']) ? 'success' : 'danger' }}">@if ($t->isKoreksi())<i class="fa-solid fa-rotate me-1"></i>@endif{{ $t->jenis_label }}</span></td><td class="num fw-semibold text-end small">{{ $t->nominal_rupiah }}</td><td class="small">{{ $t->user?->nama_petugas ?? '-' }}</td><td><span class="status-badge {{ $t->status === 'berhasil' ? 'green' : ($t->status === 'pending' ? 'orange' : ($t->status === 'ditolak' ? 'red' : 'gray')) }}">{{ $t->status === 'berhasil' ? 'Berhasil' : $t->status_label }}</span></td><td>@include('transaksi.partials.aksi', ['transaksi' => $t])</td></tr>@empty<tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-inbox"></i></div><div class="empty-title">Belum ada transaksi hari ini</div><div class="empty-desc">Proses transaksi baru melalui halaman transaksi.</div></div></td></tr>@endforelse</tbody></table></div></div></div>
        </div>
    </div>
@endsection
