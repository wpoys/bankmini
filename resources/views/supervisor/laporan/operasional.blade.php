@extends('layouts.app')

@section('title', 'Laporan Operasional')
@section('page-title', 'Laporan Operasional')
@section('page-subtitle', 'Ringkasan arus kas dan transaksi berdasarkan periode')

@section('content')
    @php
        $filterParams = request()->except('format');
    @endphp
    <div class="page-animate operational-report-page">
        @if ($errors->any())
            <div class="alert alert-danger no-print">{{ $errors->first() }}</div>
        @endif

        <div class="list-toolbar list-toolbar-filter operational-filter no-print">
            <div class="operational-export-actions mb-2">
                <a href="{{ route('supervisor.laporan.operasional', array_merge($filterParams, ['format' => 'excel'])) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-file-excel me-1"></i>Excel</a>
                <a href="{{ route('supervisor.laporan.operasional', array_merge($filterParams, ['format' => 'pdf'])) }}" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-file-pdf me-1"></i>PDF</a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print me-1"></i>Cetak</button>
            </div>
            <form method="GET" action="{{ route('supervisor.laporan.operasional') }}" id="formLaporanOperasional">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4 col-lg-3">
                            <label for="jenisPeriode" class="form-label">Jenis Periode</label>
                            <select name="periode" id="jenisPeriode" class="form-select form-select-sm">
                                <option value="harian" @selected($periode === 'harian')>Harian</option>
                                <option value="bulanan" @selected($periode === 'bulanan')>Bulanan</option>
                                <option value="tahunan" @selected($periode === 'tahunan')>Tahunan</option>
                                <option value="rentang" @selected($periode === 'rentang')>Rentang tanggal</option>
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3" data-period-field="harian" @if ($periode !== 'harian') hidden @endif>
                            <label for="tanggalLaporan" class="form-label">Tanggal</label>
                            <input type="date" id="tanggalLaporan" name="tanggal" class="form-control form-control-sm" value="{{ $mulai->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4 col-lg-3" data-period-field="bulanan" @if ($periode !== 'bulanan') hidden @endif>
                            <label for="bulanLaporan" class="form-label">Bulan</label>
                            <input type="month" id="bulanLaporan" name="bulan" class="form-control form-control-sm" value="{{ $mulai->format('Y-m') }}">
                        </div>
                        <div class="col-md-4 col-lg-3" data-period-field="tahunan" @if ($periode !== 'tahunan') hidden @endif>
                            <label for="tahunLaporan" class="form-label">Tahun</label>
                            <select id="tahunLaporan" name="tahun" class="form-select form-select-sm">
                                @for ($tahunOption = now()->year; $tahunOption >= now()->year - 10; $tahunOption--)
                                    <option value="{{ $tahunOption }}" @selected((int) $mulai->format('Y') === $tahunOption)>{{ $tahunOption }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-4 col-lg-3" data-period-field="rentang" @if ($periode !== 'rentang') hidden @endif>
                            <label for="mulaiLaporan" class="form-label">Dari</label>
                            <input type="date" id="mulaiLaporan" name="mulai" class="form-control form-control-sm" value="{{ $mulai->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4 col-lg-3" data-period-field="rentang" @if ($periode !== 'rentang') hidden @endif>
                            <label for="sampaiLaporan" class="form-label">Sampai</label>
                            <input type="date" id="sampaiLaporan" name="sampai" class="form-control form-control-sm" value="{{ $sampai->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-5 col-lg-4">
                            <label for="tellerLaporan" class="form-label">Petugas / Teller (opsional)</label>
                            <select name="teller" id="tellerLaporan" class="form-select form-select-sm">
                                <option value="">Semua Teller</option>
                                @foreach ($tellerList as $teller)
                                    <option value="{{ $teller->id }}" @selected($tellerTerpilih?->id === $teller->id)>{{ $teller->nama_petugas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter me-1"></i>Tampilkan</button>
                        </div>
                    </div>
                    <div class="operational-shortcuts">
                        <span>Pilihan Cepat:</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-period-shortcut="harian">Hari Ini</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-period-shortcut="bulanan">Bulan Ini</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-period-shortcut="tahunan">Tahun Ini</button>
                        <span class="small text-muted ms-auto">Filter tambahan: Teller</span>
                    </div>
            </form>
        </div>

        <section class="data-panel operational-chart-panel">
            <div class="panel-head">
                <div>
                    <div class="panel-title"><span class="dot"></span>Grafik Arus Kas</div>
                    <div class="operational-chart-subtitle">Komparasi nominal setoran versus penarikan per {{ $chartMode === 'jam' ? 'jam' : ($chartMode === 'hari' ? 'hari' : 'bulan') }} · {{ $mulai->format('d/m/Y') }}–{{ $sampai->format('d/m/Y') }}</div>
                </div>
                <div class="operational-chart-legend"><span class="legend-deposit">Setoran</span><span class="legend-withdrawal">Penarikan</span></div>
            </div>
            <div class="panel-body operational-chart-body"><canvas id="operationalCashflowChart" aria-label="Grafik arus kas"></canvas></div>
        </section>

        <section class="data-panel operational-summary-panel">
            <div class="panel-head"><div class="panel-title"><span class="dot"></span>Ringkasan Periode</div><span class="small text-muted">{{ $tellerTerpilih?->nama_petugas ?? 'Semua teller' }}</span></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <tbody>
                        <tr><th>Periode</th><td>{{ $mulai->format('d/m/Y') }} s/d {{ $sampai->format('d/m/Y') }}</td><th>Hari tercakup</th><td>{{ $jumlahHari }}</td></tr>
                        <tr><th>Jumlah laporan kas</th><td>{{ $laporan->count() }}</td><th>Jumlah transaksi</th><td>{{ number_format($totalTransaksi, 0, ',', '.') }}</td></tr>
                        <tr><th>Total setoran</th><td class="num">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td><th>Total penarikan</th><td class="num">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td></tr>
                        <tr><th>Saldo awal kas</th><td class="num">Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</td><th>Saldo akhir sistem</th><td class="num fw-bold">Rp {{ number_format($saldoAkhirPeriode, 0, ',', '.') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-panel operational-detail-panel">
            <div class="panel-head"><div class="panel-title"><span class="dot"></span>Rincian Kas Harian</div><span class="small text-muted">{{ $laporan->count() }} laporan</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead><tr><th>Tanggal</th><th>Teller</th><th>Status</th><th class="text-end">Saldo Awal</th><th class="text-end">Setoran</th><th class="text-end">Penarikan</th><th class="text-end">Saldo Akhir Sistem</th><th class="text-end">Uang Fisik</th><th class="text-end">Selisih</th></tr></thead>
                    <tbody>
                        @forelse ($laporan as $item)
                            <tr>
                                <td>{{ $item->tanggal->format('d/m/Y') }}</td><td>{{ $item->teller?->nama_petugas ?? '-' }}</td>
                                <td><span class="badge badge-soft-{{ in_array($item->status, ['disetujui', 'approved']) ? 'success' : (in_array($item->status, ['menunggu_approval', 'pending']) ? 'warning' : (in_array($item->status, ['ditolak', 'rejected']) ? 'danger' : 'secondary')) }}">{{ $item->status_label }}</span></td>
                                <td class="num text-end">Rp {{ number_format($item->saldo_awal_kas, 0, ',', '.') }}</td><td class="num text-end">Rp {{ number_format($item->total_setoran, 0, ',', '.') }}</td>
                                <td class="num text-end">Rp {{ number_format($item->total_penarikan, 0, ',', '.') }}</td><td class="num text-end">Rp {{ number_format($item->saldo_akhir_sistem, 0, ',', '.') }}</td>
                                <td class="num text-end">{{ $item->saldo_akhir_fisik !== null ? 'Rp '.number_format($item->saldo_akhir_fisik, 0, ',', '.') : '-' }}</td><td class="num text-end {{ $item->selisih ? 'text-danger' : 'text-success' }}">{{ $item->selisih !== null ? 'Rp '.number_format($item->selisih, 0, ',', '.') : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9"><div class="empty-state"><div class="empty-title">Tidak ada laporan kas pada periode ini</div></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="data-panel operational-ledger-panel">
            <div class="panel-head"><div class="panel-title"><span class="dot"></span>Buku Besar / Rincian Transaksi</div><span class="small text-muted">{{ $mutasi->count() }} transaksi</span></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-nowrap mb-0">
                    <thead><tr><th>Tanggal</th><th>Waktu</th><th>No. Struk</th><th>Teller</th><th>No. Rekening</th><th>Nasabah</th><th>Jenis</th><th class="text-end">Debit (Setoran)</th><th class="text-end">Kredit (Penarikan)</th><th class="text-end">Saldo</th></tr></thead>
                    <tbody>
                        <tr class="table-active"><td colspan="9" class="fw-semibold">Saldo awal</td><td class="num fw-bold text-end">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td></tr>
                        @forelse ($mutasi as $item)
                            <tr><td>{{ $item['tanggal']?->format('d/m/Y') }}</td><td>{{ $item['waktu']?->format('H:i') ?? '-' }}</td><td><code>{{ $item['no_struk'] }}</code></td><td>{{ $item['teller'] }}</td><td><code>{{ $item['rekening'] }}</code></td><td>{{ $item['nasabah'] }}</td><td>{{ $item['jenis'] }}</td><td class="num text-end">{{ $item['debit'] ? 'Rp '.number_format($item['debit'], 0, ',', '.') : '-' }}</td><td class="num text-end">{{ $item['kredit'] ? 'Rp '.number_format($item['kredit'], 0, ',', '.') : '-' }}</td><td class="num fw-semibold text-end">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td></tr>
                        @empty
                            <tr><td colspan="10"><div class="empty-state"><div class="empty-title">Tidak ada transaksi pada periode ini</div></div></td></tr>
                        @endforelse
                    </tbody>
                    @if ($mutasi->isNotEmpty())
                        <tfoot><tr class="fw-bold total-row"><td colspan="7" class="text-end">TOTAL</td><td class="num text-end">Rp {{ number_format($mutasi->sum('debit'), 0, ',', '.') }}</td><td class="num text-end">Rp {{ number_format($mutasi->sum('kredit'), 0, ',', '.') }}</td><td class="num text-end">Rp {{ number_format($mutasi->last()['saldo'], 0, ',', '.') }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const periodSelect = document.getElementById('jenisPeriode');
            const form = document.getElementById('formLaporanOperasional');
            if (!periodSelect || !form) return;

            const updatePeriodFields = function () {
                form.querySelectorAll('[data-period-field]').forEach(function (field) {
                    const active = field.dataset.periodField === periodSelect.value;
                    field.hidden = !active;
                    field.querySelectorAll('input, select').forEach(function (control) {
                        control.disabled = !active;
                    });
                });
            };

            periodSelect.addEventListener('change', updatePeriodFields);
            updatePeriodFields();

            form.querySelectorAll('[data-period-shortcut]').forEach(function (button) {
                button.addEventListener('click', function () {
                    periodSelect.value = button.dataset.periodShortcut;
                    updatePeriodFields();
                    if (periodSelect.value === 'harian') {
                        form.querySelector('[name="tanggal"]').value = '{{ today()->toDateString() }}';
                    } else if (periodSelect.value === 'bulanan') {
                        form.querySelector('[name="bulan"]').value = '{{ now()->format('Y-m') }}';
                    } else if (periodSelect.value === 'tahunan') {
                        form.querySelector('[name="tahun"]').value = '{{ now()->year }}';
                    }
                    form.requestSubmit();
                });
            });

            const labels = @json($chartLabels);
            const setoran = @json($chartSetoran);
            const penarikan = @json($chartPenarikan);
            const canvas = document.getElementById('operationalCashflowChart');
            if (canvas && window.Chart) {
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [
                            { label: 'Setoran', data: setoran, backgroundColor: '#10b981', borderRadius: 3, barPercentage: .68, categoryPercentage: .72 },
                            { label: 'Penarikan', data: penarikan, backgroundColor: '#f43f5e', borderRadius: 3, barPercentage: .68, categoryPercentage: .72 },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { display: false }, tooltip: { callbacks: { label: (context) => context.dataset.label + ': Rp ' + Number(context.raw || 0).toLocaleString('id-ID') } } },
                        scales: {
                            y: { beginAtZero: true, ticks: { callback: (value) => 'Rp ' + Number(value).toLocaleString('id-ID'), maxTicksLimit: 5 }, grid: { color: '#edf0f5' } },
                            x: { grid: { display: false }, ticks: { maxTicksLimit: 12, maxRotation: 0, autoSkip: true } },
                        },
                    },
                });
            }
        })();
    </script>
@endpush
