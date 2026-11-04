@extends('layouts.app')

@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')
@section('page-subtitle', 'Riwayat aktivitas seluruh pengguna sistem')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Audit Trail</h1>
                <div class="page-header-sub">Riwayat aktivitas seluruh pengguna sistem</div>
            </div>
        </div>

        <div class="data-panel">
        <div class="panel-head">
            <div class="panel-title"><span class="dot"></span>Log Aktivitas (100 terakhir)</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aktivitas</th>
                        <th>Deskripsi</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small text-muted">{{ $log->created_at->format('d M Y H:i:s') }}</td>
                            <td class="fw-semibold small">{{ $log->user?->nama_petugas ?? 'System' }}
                                <span class="badge badge-soft-secondary ms-1">{{ $log->user?->username ?? '-' }}</span>
                            </td>
                            <td>
                                <span class="badge badge-soft-primary">{{ $log->aktivitas }}</span>
                            </td>
                            <td class="small">{{ $log->deskripsi }}</td>
                            <td class="small text-muted"><code>{{ $log->ip_address ?? '-' }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-shield-halved"></i></div>
                                    <div class="empty-title">Belum ada log aktivitas</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        </div>
    </div>
@endsection
