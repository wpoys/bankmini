@extends('layouts.app')

@section('title', 'Pengajuan Koreksi')
@section('page-title', 'Pengajuan Koreksi')
@section('page-subtitle', 'Ajukan koreksi atas transaksi yang keliru')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Pengajuan Koreksi</h1>
                <div class="page-header-sub">Koreksi atas transaksi yang keliru di loket Anda</div>
            </div>
            <div class="header-actions">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAjukanKoreksi">
                    <i class="fa-solid fa-plus me-1"></i>Ajukan Koreksi
                </button>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Pengajuan</div>
                <div class="table-toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah / alasan...">
                    </div>
                    <select class="form-select form-select-sm d-inline-block" style="width:auto;" data-table-filter>
                        <option value="">Semua Status</option>
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
                            <th>Transaksi</th>
                            <th>Nasabah</th>
                            <th>Jenis Asli</th>
                            <th class="text-end">Nominal</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($koreksi as $k)
                            @php $t = $k->transaksi; @endphp
                            <tr data-search="{{ $t?->no_struk }} {{ $k->nasabah->nama_siswa }} {{ $k->alasan }}" data-status="{{ $k->status }}">
                                <td class="small text-muted">{{ $k->created_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if ($t)
                                        <code class="small">{{ $t->no_struk }}</code>
                                    @else
                                        <span class="badge badge-soft-warning">Tanpa asli (temuan)</span>
                                    @endif
                                </td>
                                <td class="fw-semibold small">{{ $k->nasabah->nama_siswa }}
                                    @if ($k->jenis_label)
                                        <br><span class="badge badge-soft-info">{{ $k->jenis_label }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($t)
                                        <span class="badge badge-soft-{{ $t->jenis_trans === 'setoran' ? 'success' : 'danger' }}">{{ $t->jenis_label }}</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="num fw-semibold text-end small">Rp {{ number_format($k->nominal_efektif, 0, ',', '.') }}</td>
                                <td class="small text-muted" style="max-width:260px;">
                                    <span class="d-inline-block text-truncate w-100" title="{{ $k->alasan }}">{{ $k->alasan }}</span>
                                </td>
                                <td>
                                    @php
                                        $badge = match ($k->status) {
                                            'menunggu_approval' => 'warning',
                                            'disetujui' => 'success',
                                            'ditolak' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge badge-soft-{{ $badge }}">{{ $k->status_label }}</span>
                                </td>
                                <td>
                                    @if ($t)
                                        @include('transaksi.partials.aksi', ['transaksi' => $t])
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-rotate-left"></i></div>
                                        <div class="empty-title">Belum ada pengajuan koreksi</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($koreksi->hasPages())
                <div class="p-2 border-top">{{ $koreksi->links() }}</div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalAjukanKoreksi" tabindex="-1" aria-labelledby="modalAjukanKoreksiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAjukanKoreksiLabel">
                        <i class="fa-solid fa-rotate-left text-primary me-2"></i>Ajukan Koreksi Transaksi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0" style="height: 78vh;">
                    <iframe src="{{ route('teller.koreksi.create', ['modal' => 1]) }}" title="Form Pengajuan Koreksi" class="w-100 h-100 border-0"></iframe>
                </div>
            </div>
        </div>
    </div>
@endsection
