@extends('layouts.app')

@section('title', 'Verifikasi Laporan Harian')
@section('page-title', 'Verifikasi Laporan Harian')
@section('page-subtitle', 'Periksa dan verifikasi laporan harian teller')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Verifikasi Laporan Harian</h1>
                <div class="page-header-sub">Periksa laporan harian yang dikirim teller</div>
            </div>
            <div class="header-actions">
                <form method="GET" class="d-flex gap-2">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" @selected(request('status') === 'all' || !request('status'))>Semua Status</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="menunggu_approval" @selected(request('status') === 'menunggu_approval')>Menunggu Approval</option>
                        <option value="pending" @selected(request('status') === 'pending')>Ditahan (Pending)</option>
                        <option value="disetujui" @selected(request('status') === 'disetujui')>Disetujui</option>
                        <option value="ditolak" @selected(request('status') === 'ditolak')>Ditolak</option>
                    </select>
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                </form>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Daftar Laporan Teller</div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Teller</th>
                            <th class="text-end">Setoran</th>
                            <th class="text-end">Penarikan</th>
                            <th class="text-end">Saldo Akhir Sistem</th>
                            <th>Selisih</th>
                            <th>Status</th>
                            <th>Dikirim</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporan as $l)
                            @php
                                $color = match ($l->status) {
                                    'draft' => 'secondary',
                                    'menunggu_approval' => 'warning',
                                    'pending' => 'info',
                                    'disetujui' => 'success',
                                    'ditolak' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold small">{{ $l->tanggal->format('d M Y') }}</td>
                                <td class="small">{{ $l->teller->nama_petugas }}</td>
                                <td class="num small text-end">Rp {{ number_format($l->total_setoran, 0, ',', '.') }}</td>
                                <td class="num small text-end">Rp {{ number_format($l->total_penarikan, 0, ',', '.') }}</td>
                                <td class="num small text-end">Rp {{ number_format($l->saldo_akhir_sistem, 0, ',', '.') }}</td>
                                <td>
                                    @if ($l->selisih === null)
                                        <span class="text-muted small">-</span>
                                    @elseif ($l->selisih == 0)
                                        <span class="status-badge green">Selaras</span>
                                    @else
                                        <span class="status-badge red">Selisih</span>
                                    @endif
                                </td>
                                <td><span class="badge badge-soft-{{ $color }}">{{ $l->status_label }}</span></td>
                                <td class="small text-muted">{{ $l->submitted_at?->format('d/m H:i') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('supervisor.verifikasi.show', $l->id) }}" class="btn btn-sm btn-outline-primary">
                                        {{ $l->status === 'menunggu_approval' ? 'Verifikasi' : 'Detail' }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                                        <div class="empty-title">Tidak ada laporan</div>
                                        <div class="empty-desc">Laporan harian teller akan tampil di sini.</div>
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
