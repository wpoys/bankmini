@extends('layouts.app')

@section('title', 'Laporan & Akuntansi')
@section('page-title', 'Laporan & Akuntansi')
@section('page-subtitle', 'Rekapitulasi keuangan dan simpanan')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Laporan &amp; Akuntansi</h1>
                <div class="page-header-sub">Rekapitulasi keuangan dan simpanan</div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Setoran Bulan Ini</span>
                        <span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-down"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalSetoranBulanIni, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Penarikan Bulan Ini</span>
                        <span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-up"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalPenarikanBulanIni, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Total Dana Simpanan</span>
                        <span class="stat-icon bg-primary-subtle text-primary"><i class="fa-solid fa-wallet"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalSimpanan, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card h-100">
                    <div class="stat-top">
                        <span class="stat-label">Total Transaksi</span>
                        <span class="stat-icon bg-info-subtle text-info"><i class="fa-solid fa-chart-pie"></i></span>
                    </div>
                    <div class="stat-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--green)"></span>Arus Kas 12 Bulan Terakhir</div>
            </div>
            <div class="panel-body p-3">
                <div class="chart-container chart-lg">
                    <canvas id="grafikArusKas"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        new Chart(document.getElementById('grafikArusKas'), {
            type: 'bar',
            data: {
                labels: @json($transaksiBulan['labels']),
                datasets: [
                    { label: 'Setoran', data: @json($transaksiBulan['setoran']), backgroundColor: 'rgba(22,163,74,.75)', type: 'bar' },
                    { label: 'Penarikan', data: @json($transaksiBulan['penarikan']), backgroundColor: 'rgba(220,38,38,.75)', type: 'bar' },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
@endpush
