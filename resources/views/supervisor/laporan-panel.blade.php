@extends('layouts.app')

@section('title', 'Ekspor Laporan')
@section('page-title', 'Ekspor Laporan')
@section('page-subtitle', 'Pilih jenis laporan, rentang tanggal, lalu unduh dalam format PDF')

@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Ekspor Laporan</h1>
                <div class="page-header-sub">Pilih jenis laporan, rentang tanggal, lalu unduh dalam format PDF</div>
            </div>
        </div>

        <div class="data-panel mb-3">
            <div class="panel-head">
                <div class="panel-title"><span class="dot"></span>Panel Ekspor Laporan</div>
            </div>
            <div class="panel-body p-3">
            <form method="GET" class="filter-bar">
                <div class="filter-field">
                    <label class="form-label">Jenis Laporan</label>
                    <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach ($jenisList as $kode => $label)
                            <option value="{{ $kode }}" @selected($jenis === $kode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-field-wide">
                    <label class="form-label">Rentang Tanggal</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="mulai" value="{{ old('mulai', $mulai->format('Y-m-d')) }}" class="form-control form-control-sm">
                        <input type="date" name="sampai" value="{{ old('sampai', $sampai->format('Y-m-d')) }}" class="form-control form-control-sm">
                    </div>
                </div>

                @if (in_array($jenis, ['mutasi-nasabah', 'kartu-simpanan'], true))
                    <div class="filter-field">
                        <label class="form-label">Nasabah</label>
                        <select name="nasabah_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">— Pilih Nasabah —</option>
                            @foreach ($nasabahList as $n)
                                <option value="{{ $n->id }}" @selected((int) $nasabahId === (int) $n->id)>{{ $n->no_rekening }} — {{ $n->nama_siswa }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($jenis === 'laporan-tahunan')
                    <div class="filter-field">
                        <label class="form-label">Tahun</label>
                        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                            @for ($t = now()->year; $t >= now()->year - 5; $t--)
                                <option value="{{ $t }}" @selected((int) $tahun === $t)>{{ $t }}</option>
                            @endfor
                        </select>
                    </div>
                @endif

                <div class="filter-action">
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-eye me-1"></i>Lihat Ringkasan</button>
                </div>

                @if (! ($summary['none'] ?? false))
                    <div class="filter-action filter-spacer d-flex gap-2 flex-wrap">
                        <a href="{{ exportUrl('excel') }}" class="btn btn-sm btn-success"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                        <a href="{{ exportUrl('pdf') }}" class="btn btn-sm btn-danger"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
                    </div>
                @endif

                @php
                    function exportUrl(string $format): string
                    {
                        $params = ['format' => $format, 'jenis' => request('jenis', 'jurnal')];
                        $jenis = request('jenis', 'jurnal');

                        if (in_array($jenis, ['jurnal'], true)) {
                            $params['mulai'] = request('mulai');
                            $params['sampai'] = request('sampai');
                        }

                        if (in_array($jenis, ['mutasi-nasabah', 'kartu-simpanan'], true)) {
                            $params['nasabah_id'] = request('nasabah_id');
                            $params['mulai'] = request('mulai');
                            $params['sampai'] = request('sampai');
                        }

                        if ($jenis === 'laporan-tahunan') {
                            $params['tahun'] = request('tahun');
                        }

                        return route(match ($jenis) {
                            'jurnal' => 'supervisor.laporan.jurnal',
                            'mutasi-nasabah' => 'supervisor.laporan.mutasi-nasabah',
                            'kartu-simpanan' => 'supervisor.laporan.kartu-simpanan',
                            'laporan-tahunan' => 'supervisor.laporan.laporan-tahunan',
                            default => 'supervisor.laporan.panel',
                        }, $params);
                    }
                @endphp
            </form>
            </div>
        </div>

    <div class="data-panel">
        <div class="panel-head">
            <div class="panel-title"><span class="dot" style="background:var(--orange)"></span>Ringkasan {{ $jenisList[$jenis] ?? ucfirst($jenis) }}</div>
        </div>
        <div class="panel-body p-3">
            @if ($summary['none'] ?? false)
                <p class="text-muted small mb-0">Pilih nasabah untuk melihat ringkasannya.</p>
            @elseif ($jenis === 'jurnal')
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Total Entri Jurnal</span></div>
                            <div class="stat-value">{{ number_format($summary['jumlahEntri'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Total Debit</span></div>
                            <div class="stat-value text-success">Rp {{ number_format($summary['totalDebit'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Total Kredit</span></div>
                            <div class="stat-value text-danger">Rp {{ number_format($summary['totalKredit'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="alert {{ $summary['seimbang'] ? 'alert-success' : 'alert-danger' }} small mt-3 mb-0">
                    <i class="fa-solid fa-{{ $summary['seimbang'] ? 'check' : 'xmark' }} me-1"></i>
                    Jurnal {{ $summary['seimbang'] ? 'seimbang' : 'tidak seimbang' }} (debit = kredit).
                </div>
            @elseif (in_array($jenis, ['mutasi-nasabah', 'kartu-simpanan'], true))
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label"><code>{{ $summary['rekening'] }}</code> · {{ $summary['kelas'] }}</span></div>
                            <div class="stat-value fs-6">{{ $summary['nama'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Saldo Awal</span></div>
                            <div class="stat-value">Rp {{ number_format($summary['saldoAwal'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card h-100">
                            <div class="stat-top">
                                <span class="stat-label">Debit</span>
                                <span class="stat-label text-success num">Rp {{ number_format($summary['totalDebit'], 0, ',', '.') }}</span>
                            </div>
                            <div class="stat-top">
                                <span class="stat-label">Kredit</span>
                                <span class="stat-label text-danger num">Rp {{ number_format($summary['totalKredit'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Saldo Akhir</span></div>
                            <div class="stat-value text-primary">Rp {{ number_format($summary['saldoAkhir'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @elseif ($jenis === 'laporan-tahunan')
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Total Setoran</span></div>
                            <div class="stat-value text-success">Rp {{ number_format($summary['totalSetoran'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Total Penarikan</span></div>
                            <div class="stat-value text-danger">Rp {{ number_format($summary['totalPenarikan'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Jumlah Transaksi</span></div>
                            <div class="stat-value">{{ number_format($summary['jumlahTransaksi'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-0">
                    <div class="col-md-6">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Nasabah Baru</span></div>
                            <div class="stat-value">{{ number_format($summary['nasabahBaru'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card h-100">
                            <div class="stat-top"><span class="stat-label">Total Simpanan</span></div>
                            <div class="stat-value">Rp {{ number_format($summary['saldoAkhir'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    </div>
@endsection
