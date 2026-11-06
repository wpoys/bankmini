@extends('layouts.app')

@section('title', 'Jadwal Petugas')
@section('page-title', 'Jadwal Petugas')
@section('page-subtitle', 'Jadwal kerja Teller, Supervisor, Accounting, dan Customer Service')

@section('list-page-class', 'list-page')
@php
    $routeIndex = $prefix.'.jadwal.index';
    $routeCreate = $prefix.'.jadwal.create';
    $routeEdit = $prefix.'.jadwal.edit';
    $routeBatal = $prefix.'.jadwal.batalkan';
    $statusFilterLabel = [
        'dijadwalkan' => 'Dijadwalkan',
        'bertugas' => 'Bertugas',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];
@endphp

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Jadwal Petugas</h1>
                <div class="page-header-sub">
                    Hari-hari kerja petugas — {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                </div>
            </div>
        </div>

        <div class="data-panel mb-3">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Jadwal Petugas</div>
                <div class="table-toolbar">
                    <form class="d-flex gap-2 align-items-center flex-wrap" method="GET">
                        <input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control form-control-sm" style="width:auto">
                        <select name="hari" class="form-select form-select-sm" style="width:auto">
                            <option value="">Semua Hari</option>
                            @foreach ($hariOptions as $h)
                                <option value="{{ $h }}" @selected(request('hari') === $h)>{{ $h }}</option>
                            @endforeach
                        </select>
                        <select name="status" class="form-select form-select-sm" style="width:auto">
                            <option value="">Semua Status</option>
                            @foreach ($statusOptions as $s)
                                <option value="{{ $s }}" @selected(request('status') === $s)>{{ $statusFilterLabel[$s] ?? ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                    </form>
                    @if ($canManage)
                        <a href="{{ route($routeCreate) }}" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i>Tambah Jadwal</a>
                    @endif
                    <span class="status-badge blue">{{ $jadwal->total() }} terjadwal</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Petugas</th>
                            <th>Bagian</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            @if ($canManage)
                                <th class="text-end">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jadwal as $j)
                            <tr>
                                <td class="small fw-semibold">{{ $j->tanggal?->format('d M Y') }}</td>
                                <td class="small text-muted">{{ $j->hari }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar rounded-circle" style="width:30px;height:30px;font-size:.7rem;">
                                            {{ strtoupper(substr($j->nama_petugas, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="fw-semibold small">{{ $j->nama_petugas }}</div>
                                            @if ($j->user)
                                                <div class="text-muted" style="font-size:.68rem;">{{ $j->user->role_label }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-soft-primary">{{ $j->bagian }}</span>
                                </td>
                                <td class="small">{{ $j->jam_label }}</td>
                                <td>
                                    @php
                                        $statusCls = match ($j->status) {
                                            'bertugas' => 'green',
                                            'selesai' => 'blue',
                                            'dibatalkan' => 'red',
                                            default => 'orange',
                                        };
                                    @endphp
                                    <span class="status-badge {{ $statusCls }}">{{ $j->status_label }}</span>
                                </td>
                                <td class="small text-muted">{{ $j->keterangan ?? '-' }}</td>
                                @if ($canManage)
                                    <td class="text-end">
                                        <a href="{{ route($routeEdit, $j->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        @if ($j->status === 'dijadwalkan')
                                            <form action="{{ route($routeBatal, $j->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger"
                                                        data-confirm="Batalkan jadwal {{ $j->nama_petugas }} tanggal {{ $j->tanggal?->format('d M Y') }}?">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 8 : 7 }}">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                                        <div class="empty-title">Belum ada jadwal</div>
                                        <div class="empty-desc">Belum ada petugas yang dijadwalkan pada filter ini.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($jadwal->hasPages())
                <div class="p-2 border-top">{{ $jadwal->links() }}</div>
            @endif
        </div>
    </div>
@endsection
