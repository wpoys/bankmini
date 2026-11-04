@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'LAPORAN KAS HARIAN',
    'periode' => $mulai->format('d/m/Y').' s/d '.$sampai->format('d/m/Y').(isset($tellerLabel) && $tellerLabel ? ' — Teller: '.$tellerLabel : ''),
    'tanggalCetak' => now()->format('d/m/Y H:i').' WIB',
])

@php
    $pecahanStandar = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100];
@endphp

@if($laporan->count() > 0)
<div class="section-title">REKAPITULASI PERIODE</div>
<table>
    <thead>
        <tr>
            <th style="width: 65%;">Keterangan</th>
            <th class="text-end" style="width: 35%;">Nilai</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Periode laporan</td><td class="text-end">{{ $mulai->format('d/m/Y') }} s/d {{ $sampai->format('d/m/Y') }}</td></tr>
        <tr><td>Teller</td><td class="text-end">{{ isset($tellerLabel) && $tellerLabel ? $tellerLabel : 'Semua teller' }}</td></tr>
        <tr><td>Jumlah hari tercakup</td><td class="text-end">{{ $jumlahHari }} hari</td></tr>
        <tr><td>Jumlah laporan</td><td class="text-end">{{ $laporan->count() }} laporan</td></tr>
        <tr><td>Total transaksi</td><td class="text-end">{{ $totalTransaksi }} transaksi</td></tr>
        <tr><td>Total setoran</td><td class="text-end">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td></tr>
        <tr><td>Total penarikan</td><td class="text-end">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td></tr>
        <tr><td>Saldo awal periode</td><td class="text-end">Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</td></tr>
        <tr><td>Saldo akhir sistem periode</td><td class="fw-bold text-end">Rp {{ number_format($saldoAkhirPeriode, 0, ',', '.') }}</td></tr>
    </tbody>
</table>
<div class="catatan">
    Saldo awal periode dihitung dari saldo awal laporan pertama setiap teller dalam periode, bukan penjumlahan saldo akhir harian.
    Saldo akhir sistem periode dihitung dari saldo awal periode + total setoran - total penarikan.
</div>
@if(class_exists(\App\Support\Terbilang::class))
<div class="catatan"><strong>Terbilang Saldo Akhir Sistem Periode:</strong> {{ strtoupper(\App\Support\Terbilang::rupiah((int) $saldoAkhirPeriode)) }}</div>
@endif
@endif

<div class="section-title">RINCIAN LEMBARAN: UANG FISIK</div>
<div class="catatan">
    Saldo awal periode: <strong>Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</strong>.
    Rincian pecahan di bawah merupakan penjabaran nominal saldo awal yang tersimpan pada laporan;
    sistem belum menyimpan denominasi fisik saldo awal secara terpisah.
</div>
@if($saldoAwalPeriode > 0)
<table>
    <thead>
        <tr>
            <th class="text-end" style="width: 40%;">Nominal</th>
            <th class="text-center" style="width: 25%;">Jumlah</th>
            <th class="text-end" style="width: 35%;">Total</th>
        </tr>
    </thead>
    <tbody>
        @php $sisaSaldoAwal = (int) $saldoAwalPeriode; @endphp
        @foreach($pecahanStandar as $pecahan)
            @php
                $jumlahLembar = intdiv($sisaSaldoAwal, $pecahan);
                $subtotal = $pecahan * $jumlahLembar;
                $sisaSaldoAwal -= $subtotal;
            @endphp
            @if($jumlahLembar > 0)
                <tr>
                    <td class="text-end">Rp {{ number_format($pecahan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $jumlahLembar }} lembar</td>
                    <td class="text-end">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="text-end fw-bold">TOTAL SALDO AWAL PERIODE</td>
            <td class="text-end fw-bold">Rp {{ number_format($saldoAwalPeriode, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@else
<div class="catatan empty">Tidak ada saldo awal pada periode ini.</div>
@endif

<div class="section-title" @if($laporan->count() > 0) style="page-break-before: always;" @endif>RINCIAN KAS HARIAN</div>
<table>
    <thead>
        <tr>
            <th style="width: 10%;">Tanggal</th>
            <th style="width: 18%;">Nama Teller</th>
            <th style="width: 10%;">Status</th>
            <th class="text-end" style="width: 16%;">Total Setoran</th>
            <th class="text-end" style="width: 16%;">Total Penarikan</th>
            <th class="text-end" style="width: 16%;">Saldo Akhir Sistem</th>
            <th class="text-end" style="width: 14%;">Selisih</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($laporan as $l)
            <tr>
                <td class="text-center">{{ $l->tanggal->format('d/m/Y') }}</td>
                <td>{{ $l->teller?->nama_petugas ?? '-' }}</td>
                <td class="text-center">{{ $l->status_label }}</td>
                <td class="text-end">Rp {{ number_format($l->total_setoran, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($l->total_penarikan, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($l->saldo_akhir_sistem, 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($l->selisih ?? 0, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="empty">Tidak ada data untuk periode ini</td>
            </tr>
        @endforelse
    </tbody>
    @if($laporan->count() > 0)
    <tfoot>
        <tr>
            <td colspan="3" class="text-end fw-bold">TOTAL PERIODE</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td>
            <td></td>
            <td></td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="section-title">BUKU BESAR SIMPANAN / RINCIAN TRANSAKSI PERIODE{{ $nasabah ? ' — '.$nasabah->nama_siswa.' ('.$nasabah->no_rekening.')' : '' }}</div>
<table>
    <thead>
        <tr>
            <th style="width: 8%;">Tanggal</th>
            <th style="width: 6%;">Waktu</th>
            <th style="width: 13%;">No. Struk</th>
            <th style="width: 9%;">Teller</th>
            <th style="width: 10%;">No. Rekening</th>
            <th style="width: 14%;">Nama Nasabah</th>
            <th style="width: 8%;">Jenis</th>
            <th class="text-end" style="width: 10%;">Debit (Setoran)</th>
            <th class="text-end" style="width: 10%;">Kredit (Penarikan)</th>
            <th class="text-end" style="width: 12%;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background-color: #f0f7ff;">
            <td colspan="9" class="fw-bold">SALDO AWAL</td>
            <td class="text-end fw-bold nowrap">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
        </tr>
        @forelse ($mutasi as $m)
            <tr>
                <td class="text-center">{{ $m['tanggal']->format('d/m/Y') }}</td>
                <td class="text-center">{{ $m['waktu']?->format('H:i') ?? '-' }}</td>
                <td class="nowrap">{{ $m['no_struk'] }}</td>
                <td>{{ $m['teller'] }}</td>
                <td>{{ $m['rekening'] }}</td>
                <td>{{ $m['nasabah'] }}</td>
                <td class="text-center">{{ $m['jenis'] }}</td>
                <td class="text-end nowrap">{{ $m['debit'] ? number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                <td class="text-end nowrap">{{ $m['kredit'] ? number_format($m['kredit'], 0, ',', '.') : '-' }}</td>
                <td class="text-end nowrap">{{ number_format($m['saldo'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="empty">Tidak ada detail transaksi pada periode ini</td></tr>
        @endforelse
    </tbody>
    @if($mutasi->count() > 0)
    <tfoot>
        <tr>
            <td colspan="7" class="text-end fw-bold">TOTAL TRANSAKSI ({{ $mutasi->count() }})</td>
            <td class="text-end fw-bold nowrap">{{ number_format($mutasi->sum('debit'), 0, ',', '.') }}</td>
            <td class="text-end fw-bold nowrap">{{ number_format($mutasi->sum('kredit'), 0, ',', '.') }}</td>
            <td class="text-end fw-bold nowrap">{{ number_format($mutasi->last()['saldo'] ?? $saldoAwal, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }} WIB melalui Sistem Bank Mini Sekolah | Halaman</div>
