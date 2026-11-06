@extends('layouts.app')

@section('title', 'Laporan Harian')
@section('page-title', 'Laporan Harian')
@section('page-subtitle', 'Rekapitulasi laporan kas harian teller'.(isset($tellerTerpilih) && $tellerTerpilih ? ' — '.$tellerTerpilih->nama_petugas : ''))

@section('list-page-class', 'list-page')
@section('content')
    <div class="page-animate">
        <div class="page-header">
            <div>
                <h1>Laporan Harian</h1>
                <div class="page-header-sub">
                    Rekapitulasi laporan kas harian teller
                    @if (isset($tellerTerpilih) && $tellerTerpilih)
                        &middot; Teller: {{ $tellerTerpilih->nama_petugas }}
                    @endif
                </div>
            </div>
        </div>

        @include('supervisor.laporan.partials.filter', ['rute' => 'supervisor.laporan.laporan-harian'])

    <div class="data-panel">
        <div class="panel-head">
            <div class="panel-title"><span class="dot"></span>Rekapitulasi Periode</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th class="text-end">Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Periode laporan</td><td class="text-end">{{ $mulai->format('d/m/Y') }} s/d {{ $sampai->format('d/m/Y') }}</td></tr>
                    <tr><td>Teller</td><td class="text-end">{{ $tellerTerpilih?->nama_petugas ?? 'Semua teller' }}</td></tr>
                    <tr><td>Jumlah hari tercakup</td><td class="text-end">{{ $jumlahHari }} hari</td></tr>
                    <tr><td>Jumlah laporan</td><td class="text-end">{{ $laporan->count() }}</td></tr>
                    <tr><td>Total transaksi</td><td class="text-end">{{ $totalTransaksi }}</td></tr>
                    <tr><td>Total setoran</td><td class="num text-end">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td></tr>
                    <tr><td>Total penarikan</td><td class="num text-end">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td></tr>
                    <tr><td>Saldo awal periode</td><td class="num text-end">Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</td></tr>
                    <tr><td>Saldo akhir sistem periode</td><td class="num fw-semibold text-end">Rp {{ number_format($saldoAkhirPeriode, 0, ',', '.') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="data-panel mt-3">
        <div class="panel-head">
            <div class="panel-title"><span class="dot"></span>Rincian Lembaran: Uang Fisik</div>
            <span class="small text-muted">Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-end">Nominal</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sisaSaldoAwal = (int) $saldoAwalPeriode;
                        $pecahanSaldoAwal = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100];
                    @endphp
                    @foreach ($pecahanSaldoAwal as $pecahan)
                        @php
                            $jumlahLembar = intdiv($sisaSaldoAwal, $pecahan);
                            $subtotal = $jumlahLembar * $pecahan;
                            $sisaSaldoAwal -= $subtotal;
                        @endphp
                        @if ($jumlahLembar > 0)
                            <tr>
                                <td class="num small text-end">Rp {{ number_format($pecahan, 0, ',', '.') }}</td>
                                <td class="text-end small">{{ $jumlahLembar }} lembar</td>
                                <td class="num text-end small">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endforeach
                    @if ($saldoAwalPeriode <= 0)
                        <tr><td colspan="3" class="small text-muted text-center py-2">Tidak ada saldo awal pada periode ini.</td></tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="small text-end">TOTAL SALDO AWAL PERIODE</th>
                        <th class="num text-end small">Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="panel-body px-3 pb-3 small text-muted">
            Rincian pecahan merupakan penjabaran nominal saldo awal laporan; sistem belum menyimpan denominasi fisik saldo awal secara terpisah.
        </div>
    </div>

    <div class="data-panel mt-3">
        <div class="panel-head">
            <div class="panel-title"><span class="dot"></span>Rincian Kas Harian</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Teller</th>
                        <th>Status</th>
                        <th class="text-end">Saldo Awal</th>
                        <th class="text-end">Setoran</th>
                        <th class="text-end">Penarikan</th>
                        <th class="text-end">Saldo Akhir Sistem</th>
                        <th class="text-end">Uang Fisik</th>
                        <th class="text-end">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporan as $l)
                        <tr>
                            <td class="small">{{ $l->tanggal->format('d/m/Y') }}</td>
                            <td class="fw-semibold small">{{ $l->teller?->nama_petugas ?? '-' }}</td>
                            <td><span class="badge badge-soft-{{ in_array($l->status, ['disetujui', 'approved']) ? 'success' : (in_array($l->status, ['menunggu_approval', 'pending']) ? 'warning' : (in_array($l->status, ['ditolak', 'rejected']) ? 'danger' : 'secondary')) }}">{{ $l->status_label }}</span></td>
                            <td class="num small text-end">Rp {{ number_format($l->saldo_awal_kas, 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($l->total_setoran, 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($l->total_penarikan, 0, ',', '.') }}</td>
                            <td class="num fw-semibold small text-end">Rp {{ number_format($l->saldo_akhir_sistem, 0, ',', '.') }}</td>
                            <td class="num small text-end">{{ $l->saldo_akhir_fisik !== null ? 'Rp '.number_format($l->saldo_akhir_fisik, 0, ',', '.') : '-' }}</td>
                            <td class="num small text-end {{ $l->selisih != 0 ? 'text-danger fw-bold' : 'text-success' }}">{{ $l->selisih !== null ? 'Rp '.number_format($l->selisih, 0, ',', '.') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-icon"><i class="fa-solid fa-calendar-day"></i></div>
                                    <div class="empty-title">Tidak ada laporan</div>
                                    <div class="empty-desc">Tidak ada laporan pada rentang tanggal tersebut.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($laporan->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold total-row">
                            <td colspan="4" class="small">TOTAL</td>
                            <td class="num small text-end">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($laporan->sum('saldo_akhir_sistem'), 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($laporan->sum('saldo_akhir_fisik'), 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($laporan->sum('selisih'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="data-panel mt-3">
        <div class="panel-head">
            <div class="panel-title"><span class="dot"></span>Buku Besar / Rincian Transaksi</div>
            <span class="small text-muted">
                @if ($nasabah)
                    {{ $nasabah->nama_siswa }} ({{ $nasabah->no_rekening }}) &middot;
                @endif
                {{ $mutasi->count() }} transaksi
            </span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle table-nowrap mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Waktu</th>
                        <th>No. Struk</th>
                        <th>Teller</th>
                        <th>No. Rekening</th>
                        <th>Nasabah</th>
                        <th>Jenis</th>
                        <th class="text-end">Debit (Setoran)</th>
                        <th class="text-end">Kredit (Penarikan)</th>
                        <th class="text-end">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="table-active">
                        <td colspan="9" class="small fw-semibold">Saldo awal</td>
                        <td class="num small fw-bold text-end">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                    </tr>
                    @forelse ($mutasi as $m)
                        <tr>
                            <td class="small">{{ $m['tanggal']->format('d/m/Y') }}</td>
                            <td class="small">{{ $m['waktu']?->format('H:i') ?? '-' }}</td>
                            <td><code class="small">{{ $m['no_struk'] }}</code></td>
                            <td class="small">{{ $m['teller'] }}</td>
                            <td class="small"><code>{{ $m['rekening'] }}</code></td>
                            <td class="small">{{ $m['nasabah'] }}</td>
                            <td><span class="badge badge-soft-{{ $m['jenis'] === 'Setoran' ? 'success' : 'danger' }}">{{ $m['jenis'] }}</span></td>
                            <td class="num small text-end {{ $m['debit'] ? 'text-success' : '' }}">{{ $m['debit'] ? 'Rp '.number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                            <td class="num small text-end {{ $m['kredit'] ? 'text-danger' : '' }}">{{ $m['kredit'] ? 'Rp '.number_format($m['kredit'], 0, ',', '.') : '-' }}</td>
                            <td class="num fw-semibold small text-end">Rp {{ number_format($m['saldo'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="empty-state"><div class="empty-title">Tidak ada detail transaksi</div></div></td></tr>
                    @endforelse
                </tbody>
                @if ($mutasi->isNotEmpty())
                    <tfoot>
                        <tr class="fw-bold total-row">
                            <td colspan="7" class="small text-end">TOTAL</td>
                            <td class="num small text-end">Rp {{ number_format($mutasi->sum('debit'), 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($mutasi->sum('kredit'), 0, ',', '.') }}</td>
                            <td class="num small text-end">Rp {{ number_format($mutasi->last()['saldo'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    </div>
@endsection
