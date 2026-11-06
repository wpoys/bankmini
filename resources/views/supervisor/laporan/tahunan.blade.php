@extends('layouts.app')

@section('title', 'Laporan Tahunan')
@section('page-title', 'Laporan Tahunan')
@section('page-subtitle', 'Rekapitulasi keuangan bank mini per tahun')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Laporan Tahunan</h1>
                <div class="page-header-sub">Rekapitulasi keuangan bank mini per tahun</div>
            </div>
        </div>

        <div class="list-toolbar">
            <form class="d-flex gap-2 align-items-end flex-wrap" method="GET">
                    <div>
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-select form-select-sm">
                            @for ($t = now()->year; $t >= now()->year - 5; $t--)
                                <option value="{{ $t }}" @selected($tahun === $t)>{{ $t }}</option>
                            @endfor
                        </select>
                    </div>
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter me-1"></i>Tampilkan</button>
                </form>
                <div class="ms-auto">
                    <a href="{{ route('supervisor.laporan.laporan-tahunan', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                    <a href="{{ route('supervisor.laporan.laporan-tahunan', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
                </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Total Setoran</span>
                        <span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-down"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Total Penarikan</span>
                        <span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-up"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Nasabah Baru</span>
                        <span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-user-plus"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($nasabahBaru, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Saldo Akhir Pos Saldo</span>
                        <span class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-wallet"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Rekapitulasi Bulanan {{ $tahun }}</div>
            </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="text-end">Total Setoran</th>
                        <th class="text-end">Total Penarikan</th>
                        <th class="text-end">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($perBulan as $b)
                        <tr>
                            <td class="fw-semibold small">{{ $b['bulan'] }}</td>
                            <td class="num small text-end">Rp {{ number_format($b['setoran'], 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($b['penarikan'], 0, ',', '.') }}</td>
                            <td class="num fw-semibold small text-end">Rp {{ number_format($b['setoran'] - $b['penarikan'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold total-row">
                        <td class="small">TOTAL ({{ $jumlahTransaksi }} transaksi)</td>
                        <td class="num small text-end">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td>
                        <td class="num small text-end">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td>
                        <td class="num small text-end">Rp {{ number_format($totalSetoran - $totalPenarikan, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        </div>
    </div>
@endsection
