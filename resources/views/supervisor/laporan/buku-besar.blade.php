@extends('layouts.app')

@section('title', 'Buku Besar')
@section('page-title', 'Buku Besar / Kartu Simpanan')
@section('page-subtitle', 'Pergerakan mutasi seluruh simpanan nasabah')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Buku Besar</h1>
                <div class="page-header-sub">Pergerakan mutasi seluruh simpanan nasabah</div>
            </div>
        </div>

        @include('supervisor.laporan.partials.filter', ['rute' => 'supervisor.laporan.buku-besar'])

    <div class="data-panel">
        <div class="panel-head">
            <div class="panel-title"><span class="dot"></span>Buku Besar Simpanan</div>
            <span class="small text-muted">Saldo awal: <strong>Rp {{ number_format($saldoAwal, 0, ',', '.') }}</strong></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No. Struk</th>
                        <th>Nasabah</th>
                        <th>Rekening</th>
                        <th>Jenis</th>
                        <th class="text-end">Debit (Setoran)</th>
                        <th class="text-end">Kredit (Penarikan)</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-active">
                        <td colspan="7" class="small fw-semibold">Saldo awal</td>
                        <td class="num small fw-bold text-end">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                    </tr>
                    @forelse ($mutasi as $m)
                        <tr>
                            <td class="small">{{ $m['tanggal']->format('d/m/Y') }}</td>
                            <td><code class="small">{{ $m['no_struk'] }}</code></td>
                            <td class="fw-semibold small">{{ $m['nasabah'] }}</td>
                            <td class="small"><code>{{ $m['rekening'] }}</code></td>
                            <td><span class="badge badge-soft-{{ $m['jenis'] === 'Setoran' ? 'success' : 'danger' }}">{{ $m['jenis'] }}</span></td>
                            <td class="num small text-end {{ $m['debit'] ? 'text-success' : '' }}">{{ $m['debit'] ? 'Rp '.number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                            <td class="num small text-end {{ $m['kredit'] ? 'text-danger' : '' }}">{{ $m['kredit'] ? 'Rp '.number_format($m['kredit'], 0, ',', '.') : '-' }}</td>
                            <td class="num fw-semibold small text-end">Rp {{ number_format($m['saldo'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-book-open"></i></div>
                                    <div class="empty-title">Tidak ada transaksi</div>
                                    <div class="empty-desc">Tidak ada transaksi pada rentang tanggal tersebut.</div>
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
