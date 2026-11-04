@extends('layouts.app')

@section('title', 'Saldo per Produk')
@section('page-title', 'Saldo per Produk')
@section('page-subtitle', 'Rekapitulasi simpanan siswa vs guru/karyawan')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Saldo per Produk</h1>
                <div class="page-header-sub">Rekapitulasi simpanan siswa vs guru/karyawan</div>
            </div>
        </div>

        <div class="data-panel mb-3">
        <div class="panel-body py-3 px-3 d-flex align-items-end gap-2 flex-wrap">
            <form class="d-flex gap-2 align-items-end flex-wrap" method="GET">
                <div>
                    <label class="form-label small mb-1 fw-semibold">Per Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $tanggal->format('Y-m-d') }}" class="form-control form-control-sm">
                </div>
                <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter me-1"></i>Tampilkan</button>
            </form>
            <div class="ms-auto">
                <a href="{{ route('supervisor.laporan.saldo-produk', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                <a href="{{ route('supervisor.laporan.saldo-produk', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
            </div>
        </div>
    </div>

    <div class="data-panel">
        <div class="panel-head">
            <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Rekapitulasi Per Produk</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Produk Tabungan</th>
                        <th class="text-end">Jumlah Nasabah</th>
                        <th class="text-end">Total Saldo</th>
                        <th class="text-end">Rata-rata Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ringkasan as $r)
                        @if (isset($r['produk']))
                            <tr>
                                <td class="fw-semibold small">{{ $r['produk_label'] }}</td>
                                <td class="num small text-end">{{ number_format($r['jumlah_nasabah'], 0, ',', '.') }}</td>
                                <td class="num fw-semibold small text-end">Rp {{ number_format($r['total_saldo'], 0, ',', '.') }}</td>
                                <td class="num small text-end">Rp {{ number_format($r['jumlah_nasabah'] > 0 ? intval($r['total_saldo'] / $r['jumlah_nasabah']) : 0, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold total-row">
                        <td class="small">TOTAL</td>
                        <td class="num small text-end">{{ number_format($totalNasabah, 0, ',', '.') }}</td>
                        <td class="num small text-end">Rp {{ number_format($totalSeluruh, 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        </div>
    </div>
@endsection
