@extends('layouts.app')

@section('title', 'Detail Laporan')
@section('page-title', 'Detail Laporan Harian')
@section('page-subtitle', 'Laporan tanggal ' . $laporan->tanggal->format('d M Y'))

@section('content')
    @php
        $statusCls = match ($laporan->status) {
            'draft' => 'gray',
            'menunggu_approval' => 'orange',
            'pending' => 'blue',
            'disetujui' => 'green',
            'ditolak' => 'red',
            default => 'gray',
        };
        $statusTxt = match ($laporan->status) {
            'draft' => 'Draft',
            'menunggu_approval' => 'Menunggu',
            'pending' => 'Ditahan (Pending)',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => $laporan->status_label,
        };
    @endphp

    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Detail Laporan</h1>
                <div class="page-header-sub">{{ $laporan->tanggal->format('l, d M Y') }}</div>
            </div>
            <div class="header-actions">
                <span class="status-badge {{ $statusCls }}">{{ $statusTxt }}</span>
                <a href="{{ route('teller.laporan.index') }}" class="quick-action">
                    <i class="fa-solid fa-arrow-left"></i><span>Kembali</span>
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <div class="data-panel mb-3">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Rekapitulasi Kas</div>
                    </div>
                    <div class="panel-body p-3">
                        <dl class="row mb-0 small">
                            <dt class="col-6 fw-semibold text-muted">Teller</dt>
                            <dd class="col-6">{{ $laporan->teller->nama_petugas }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Tanggal</dt>
                            <dd class="col-6">{{ $laporan->tanggal->format('d M Y') }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Saldo Awal Kas</dt>
                            <dd class="col-6 num">Rp {{ number_format($laporan->saldo_awal_kas, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold text-success">Total Setoran</dt>
                            <dd class="col-6 num text-success">+ Rp {{ number_format($laporan->total_setoran, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold text-danger">Total Penarikan</dt>
                            <dd class="col-6 num text-danger">- Rp {{ number_format($laporan->total_penarikan, 0, ',', '.') }}</dd>
                        </dl>
                        <hr>
                        <dl class="row mb-0 small">
                            <dt class="col-6 fw-bold">Saldo Akhir Sistem</dt>
                            <dd class="col-6 num fw-bold">Rp {{ number_format($laporan->saldo_akhir_sistem, 0, ',', '.') }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Saldo Akhir Fisik</dt>
                            <dd class="col-6 num">{{ $laporan->saldo_akhir_fisik !== null ? 'Rp ' . number_format($laporan->saldo_akhir_fisik, 0, ',', '.') : '-' }}</dd>
                            <dt class="col-6 fw-semibold text-muted">Selisih</dt>
                            <dd class="col-6">
                                @if ($laporan->selisih === null)
                                    -
                                @elseif ($laporan->selisih == 0)
                                    <span class="status-badge green">Sesuai (0)</span>
                                @else
                                    <span class="status-badge red">Selisih {{ $laporan->selisih }}</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                @include('laporan.partials.rincian-saldo-awal', ['laporan' => $laporan])
                @include('laporan.partials.rincian-uang-fisik', ['laporan' => $laporan])

                @if ($laporan->kas_pindah_brankas)
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-vault me-1"></i>
                        Kas dipindahkan ke brankas: <strong>Rp {{ number_format($laporan->kas_dipindahkan, 0, ',', '.') }}</strong>
                        @if ($laporan->kas_dipindahkan_oleh)
                            oleh {{ $laporan->kasDipindahkanOleh?->nama_petugas ?? '-' }}
                            @if ($laporan->kas_dipindahkan_at)
                                pada {{ $laporan->kas_dipindahkan_at->format('d M Y H:i') }}
                            @endif
                        @else
                            <span class="text-muted">(ditetapkan Teller — jurnal pemindahan dicatat saat laporan disetujui)</span>
                        @endif
                    </div>
                @endif

                @if ($laporan->catatan_supervisor)
                    <div class="alert alert-warning small">
                        <strong><i class="fa-solid fa-sticky-note me-1"></i>Catatan Supervisor:</strong><br>
                        {{ $laporan->catatan_supervisor }}
                    </div>
                @endif

                @if ($laporan->approved_by)
                    <div class="alert alert-success small">
                        <i class="fa-solid fa-user-check me-1"></i>
                        Diverifikasi oleh <strong>{{ $laporan->approver?->nama_petugas }}</strong>
                        pada {{ $laporan->approved_at?->format('d M Y H:i') }}
                    </div>
                @endif

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <a href="{{ route('teller.laporan.create') }}" class="btn btn-outline-primary flex-fill">
                        <i class="fa-solid fa-pen me-1"></i>Edit Laporan
                    </a>
                    <a href="{{ route('teller.laporan.pdf', $laporan->id) }}" class="btn btn-outline-secondary flex-fill">
                        <i class="fa-solid fa-file-pdf me-1"></i>Unduh PDF
                    </a>
                    <button class="btn btn-outline-secondary flex-fill" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i>Cetak
                    </button>
                    @if (in_array($laporan->status, ['draft', 'pending']))
                        <form action="{{ route('teller.laporan.submit', $laporan->id) }}" method="POST" class="flex-fill" data-confirm="Kirim laporan ini ke Supervisor untuk verifikasi?" data-confirm-title="Kirim Laporan">
                            @csrf
                            @if ($laporan->status === 'pending')
                                <div class="alert alert-warning small py-2 mb-2">
                                    <i class="fa-solid fa-pause me-1"></i>
                                    Laporan sebelumnya <strong>ditahan (pending)</strong> Supervisor. Perbaiki rekonsiliasi lalu kirim ulang.
                                </div>
                            @elseif ($laporan->selisih !== null && $laporan->selisih !== 0)
                                <div class="alert alert-danger small py-2 mb-2">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                    Terdapat selisih kas Rp {{ number_format(abs((int) $laporan->selisih), 0, ',', '.') }}. Laporan tetap dapat dikirim, namun Supervisor akan menahan (pending) hingga selisih diselesaikan.
                                </div>
                            @endif
                            <button class="btn btn-primary w-100">
                                <i class="fa-solid fa-paper-plane me-1"></i>{{ $laporan->status === 'pending' ? 'Kirim Ulang untuk Verifikasi' : 'Kirim untuk Verifikasi' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="data-panel">
                    <div class="panel-head">
                        <div class="panel-title"><span class="dot"></span>Detail Transaksi ({{ $laporan->detailLaporan->count() }})</div>
                        <div class="table-toolbar">
                            <div class="search-box">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / nasabah...">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                            <thead>
                                <tr>
                                    <th>No. Struk</th>
                                    <th>Waktu</th>
                                    <th>Nasabah</th>
                                    <th>Jenis</th>
                                    <th>Petugas</th>
                                    <th class="text-end">Nominal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($laporan->detailLaporan as $detail)
                                    @php $t = $detail->transaksi; @endphp
                                    <tr data-search="{{ $t->no_struk }} {{ $t->nasabah->nama_siswa }}">
                                        <td><code class="small">{{ $t->no_struk }}</code></td>
                                        <td class="small text-muted">{{ $t->created_at->format('H:i') }}</td>
                                        <td class="fw-semibold small">{{ $t->nasabah->nama_siswa }}</td>
                                        <td><span class="badge badge-soft-{{ $t->jenis_trans === 'setoran' ? 'success' : 'danger' }}">{{ $t->jenis_label }}</span></td>
                                        <td class="small">{{ $t->user?->nama_petugas ?? '-' }}</td>
                                        <td class="num fw-semibold text-end small">{{ $t->nominal_rupiah }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-receipt"></i></div><div class="empty-title">Belum ada detail transaksi</div></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
