@extends('layouts.app')

@section('title', 'Mutasi Nasabah')
@section('page-title', 'Mutasi Nasabah')
@section('page-subtitle', 'Rincian transaksi per nasabah')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Mutasi Nasabah</h1>
                <div class="page-header-sub">Rincian transaksi per nasabah</div>
            </div>
        </div>

        <div class="list-toolbar list-toolbar-filter">
            <form class="row g-2 align-items-end" method="GET">
                <div class="col-lg-4">
                    <label class="form-label small mb-1 fw-semibold">Pilih Nasabah</label>
                    <select name="nasabah_id" class="form-select form-select-sm" required onchange="if(this.value) this.form.submit()">
                        <option value="">-- Pilih nasabah --</option>
                        @foreach ($nasabahList as $n)
                            <option value="{{ $n->id }}" @selected($nasabah?->id === $n->id)>{{ $n->nama_siswa }} ({{ $n->no_rekening }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1 fw-semibold">Dari</label>
                    <input type="date" name="mulai" value="{{ request('mulai', $nasabah ? today()->startOfMonth()->format('Y-m-d') : '') }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1 fw-semibold">Sampai</label>
                    <input type="date" name="sampai" value="{{ request('sampai', $nasabah ? today()->format('Y-m-d') : '') }}" class="form-control form-control-sm">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter me-1"></i>Tampilkan</button>
                </div>
                @if ($nasabah)
                    <div class="col-auto ms-auto">
                        <a href="{{ route('supervisor.laporan.mutasi-nasabah', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                        <a href="{{ route('supervisor.laporan.mutasi-nasabah', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
                    </div>
                @endif
            </form>
        </div>

    @if ($nasabah)
        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>{{ $nasabah->nama_siswa }} ({{ $nasabah->no_rekening }})</div>
                <span class="small text-muted">Saldo saat ini: <strong>Rp {{ number_format($nasabah->saldo, 0, ',', '.') }}</strong></span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Struk</th>
                            <th>Jenis</th>
                            <th class="text-end">Debit (Setoran)</th>
                            <th class="text-end">Kredit (Penarikan)</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="table-active">
                            <td colspan="5" class="small fw-semibold">Saldo awal</td>
                            <td class="num small fw-bold text-end">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                        </tr>
                        @forelse ($mutasi as $m)
                            <tr>
                                <td class="small">{{ $m['tanggal']->format('d/m/Y') }}</td>
                                <td><code class="small">{{ $m['no_struk'] }}</code></td>
                                <td><span class="badge badge-soft-{{ $m['jenis'] === 'Setoran' ? 'success' : 'danger' }}">{{ $m['jenis'] }}</span></td>
                                <td class="num small text-end {{ $m['debit'] ? 'text-success' : '' }}">{{ $m['debit'] ? 'Rp '.number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                                <td class="num small text-end {{ $m['kredit'] ? 'text-danger' : '' }}">{{ $m['kredit'] ? 'Rp '.number_format($m['kredit'], 0, ',', '.') : '-' }}</td>
                                <td class="num fw-semibold small text-end">Rp {{ number_format($m['saldo'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-user-clock"></i></div>
                                        <div class="empty-title">Tidak ada transaksi</div>
                                        <div class="empty-desc">Tidak ada transaksi nasabah pada rentang tanggal tersebut.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    </div>
@endsection
