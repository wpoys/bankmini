@extends('layouts.app')

@section('title', 'Buku Jurnal')
@section('page-title', 'Buku Jurnal')
@section('page-subtitle', 'Pencatatan jurnal keuangan secara rinci')

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Buku Jurnal</h1>
                <div class="page-header-sub">
                    @if ($filterLengkap)
                        {{ $mulai->format('d/m/Y') }} — {{ $sampai->format('d/m/Y') }}
                        &middot; Teller: {{ $tellerList->firstWhere('id', $tellerTerpilih)?->nama_petugas ?? '-' }}
                    @else
                        Filter rentang tanggal &amp; teller wajib diisi
                    @endif
                </div>
            </div>
            <div class="header-actions">
                @if ($filterLengkap)
                    <span class="status-badge {{ (int) $totalDebit === (int) $totalKredit ? 'green' : 'red' }}">
                        {{ (int) $totalDebit === (int) $totalKredit ? 'Balance' : 'Tidak Balance' }}
                    </span>
                    <a href="{{ route('supervisor.jurnal', ['format' => 'excel', 'mulai' => $mulai->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d'), 'kode_akun' => $kodeAkunTerpilih, 'teller' => $tellerTerpilih]) }}" class="btn btn-sm btn-success">
                        <i class="fa-solid fa-file-excel me-1"></i>Excel
                    </a>
                    <a href="{{ route('supervisor.jurnal', ['format' => 'pdf', 'mulai' => $mulai->format('Y-m-d'), 'sampai' => $sampai->format('Y-m-d'), 'kode_akun' => $kodeAkunTerpilih, 'teller' => $tellerTerpilih]) }}" class="btn btn-sm btn-danger">
                        <i class="fa-solid fa-file-pdf me-1"></i>PDF
                    </a>
                @endif
            </div>
        </div>

        <div class="list-toolbar list-toolbar-filter">
            @if ($errors->any())
                <div class="alert alert-danger py-2 small mb-3">
                    @foreach ($errors->all() as $pesanError)
                        <div><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $pesanError }}</div>
                    @endforeach
                </div>
            @endif
            <form method="GET" class="filter-bar">
                <div class="filter-field-wide">
                    <label class="form-label" for="filterMulai">Rentang Tanggal <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2">
                        <input type="date" id="filterMulai" name="mulai" value="{{ $mulai->format('Y-m-d') }}" class="form-control form-control-sm" required>
                        <input type="date" id="filterSampai" name="sampai" value="{{ $sampai->format('Y-m-d') }}" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="filter-field">
                    <label class="form-label" for="filterAkun">Kode Akun</label>
                    <select name="kode_akun" id="filterAkun" class="form-select form-select-sm">
                        <option value="">Semua Akun</option>
                        @foreach ($kodeAkunList as $kode => $nama)
                            <option value="{{ $kode }}" @selected($kodeAkunTerpilih === $kode)>{{ $kode }} — {{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-field">
                    <label class="form-label" for="filterTeller">Teller <span class="text-danger">*</span></label>
                    <select name="teller" id="filterTeller" class="form-select form-select-sm" required>
                        <option value="">— Pilih Teller —</option>
                        @foreach ($tellerList as $t)
                            <option value="{{ $t->id }}" @selected($tellerTerpilih === $t->id)>{{ $t->nama_petugas }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="filter-action">
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Tampilkan</button>
                </div>
            </form>
        </div>

        @if (! $filterLengkap)
            <div class="data-panel">
                <div class="panel-body">
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-filter-circle-xmark"></i></div>
                        <div class="empty-title">Filter wajib diisi</div>
                        <div class="empty-desc">Pilih rentang tanggal dan teller (wajib), lalu klik Tampilkan untuk memuat data jurnal.</div>
                    </div>
                </div>
            </div>
        @else
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Total Debit</span>
                        <span class="stat-icon bg-success-subtle text-success"><i class="fa-solid fa-arrow-down"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalDebit, 0, ',', '.') }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-top">
                        <span class="stat-label">Total Kredit</span>
                        <span class="stat-icon bg-danger-subtle text-danger"><i class="fa-solid fa-arrow-up"></i></span>
                    </div>
                    <div class="stat-value">Rp {{ number_format($totalKredit, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        <div class="data-panel">
            <div class="panel-head">
                <div class="panel-title"><span class="dot" style="background:var(--purple)"></span>Data Jurnal</div>
                <div class="table-toolbar">
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" class="form-control form-control-sm" data-table-filter placeholder="Cari struk / akun / nasabah...">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0" data-filterable>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nasabah</th>
                            <th>Produk</th>
                            <th>No. Struk</th>
                            <th>Kode Akun</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jurnal as $j)
                            @php
                                $isDebit = $j->posisi === 'debit';
                                $produkLabel = $j->transaksi?->nasabah?->produk_label ?? '-';
                                $searchKey = ($j->transaksi?->no_struk ?? '') . ' ' . ($j->kode_akun ?? '') . ' ' . ($j->transaksi?->nasabah?->nama_siswa ?? '') . ' ' . $produkLabel;
                            @endphp
                            <tr data-search="{{ $searchKey }}">
                                <td class="small text-muted">{{ $j->transaksi?->tanggal?->format('d/m/Y') ?? $j->created_at->format('d/m/Y') }}</td>
                                <td class="fw-semibold small">{{ $j->transaksi?->nasabah?->nama_siswa ?? 'Non-transaksi' }}</td>
                                <td class="small">{{ $produkLabel }}</td>
                                <td><code class="small">{{ $j->transaksi?->no_struk ?? '-' }}</code></td>
                                <td>
                                    @php
                                        $warnaKode = str_starts_with($j->kode_akun, '1') ? 'blue' : (str_starts_with($j->kode_akun, '4') ? 'purple' : 'gray');
                                    @endphp
                                    <span class="status-badge {{ $warnaKode }}">{{ $j->kode_akun }}</span>
                                </td>
                                <td class="num small text-end {{ $isDebit ? 'text-success fw-semibold' : 'text-muted' }}">
                                    {{ $isDebit ? 'Rp '.number_format($j->jumlah, 0, ',', '.') : '-' }}
                                </td>
                                <td class="num small text-end {{ ! $isDebit ? 'text-danger fw-semibold' : 'text-muted' }}">
                                    {{ ! $isDebit ? 'Rp '.number_format($j->jumlah, 0, ',', '.') : '-' }}
                                </td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                        <div class="empty-icon"><i class="fa-solid fa-book-open"></i></div>
                                        <div class="empty-title">Tidak ada data jurnal</div>
                                        <div class="empty-desc">Tidak ada jurnal pada rentang tanggal terpilih.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($jurnal->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold" style="background:#F8FAFD;">
                                <td colspan="5" class="text-end text-uppercase small text-muted">Total</td>
                                <td class="num small text-end text-success">Rp {{ number_format($totalDebit, 0, ',', '.') }}</td>
                                <td class="num small text-end text-danger">Rp {{ number_format($totalKredit, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @endif
    </div>
@endsection
