<?php
$path = __DIR__.'/resources/views/supervisor/laporan/pdf/harian.blade.php';
$c = file_get_contents($path);
$old = <<<'EOD'
<div class="section-title">RINCIAN TRANSAKSI PERIODE</div>
<table>
    <thead>
        <tr>
            <th style="width: 10%;">Tanggal</th>
            <th style="width: 8%;">Waktu</th>
            <th style="width: 14%;">No. Struk</th>
            <th style="width: 16%;">Teller</th>
            <th style="width: 15%;">No. Rekening</th>
            <th style="width: 19%;">Nasabah</th>
            <th style="width: 10%;">Jenis</th>
            <th class="text-end" style="width: 18%;">Nominal</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($rincianTransaksi as $detail)
            @php($t = $detail['transaksi'])
            <tr>
                <td class="text-center">{{ $detail['laporan']->tanggal->format('d/m/Y') }}</td>
                <td class="text-center">{{ $t->created_at?->format('H:i') ?? '-' }}</td>
                <td>{{ $t->no_struk }}</td>
                <td>{{ $detail['laporan']->teller?->nama_petugas ?? '-' }}</td>
                <td>{{ $t->nasabah?->no_rekening ?? '-' }}</td>
                <td>{{ $t->nasabah?->nama_siswa ?? '-' }}</td>
                <td class="text-center">{{ $t->jenis_label }}</td>
                <td class="text-end">Rp {{ number_format($t->nominal, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">Tidak ada detail transaksi pada periode ini</td></tr>
        @endforelse
    </tbody>
    @if($rincianTransaksi->count() > 0)
    <tfoot>
        <tr>
            <td colspan="7" class="text-end fw-bold">TOTAL TRANSAKSI ({{ $rincianTransaksi->count() }})</td>
            <td class="text-end fw-bold">Rp {{ number_format($rincianTransaksi->sum(fn ($detail) => $detail['transaksi']->nominal), 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>
EOD;
$new = <<<'EOD'
<div class="section-title">BUKU BESAR / RINCIAN TRANSAKSI PERIODE</div>
<table>
    <thead>
        <tr>
            <th style="width: 9%;">Tanggal</th>
            <th style="width: 7%;">Waktu</th>
            <th style="width: 13%;">No. Struk</th>
            <th style="width: 12%;">Teller</th>
            <th style="width: 13%;">No. Rekening</th>
            <th style="width: 17%;">Nama Nasabah</th>
            <th style="width: 9%;">Jenis</th>
            <th class="text-end" style="width: 12%;">Debit (Setoran)</th>
            <th class="text-end" style="width: 12%;">Kredit (Penarikan)</th>
            <th class="text-end" style="width: 13%;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        @php $saldoRunning = 0; @endphp
        @if(isset($rincianTransaksi) && $rincianTransaksi->count() > 0)
            @php $saldoRunning = (int) ($rincianTransaksi->first()['laporan']->saldo_awal_kas ?? 0); @endphp
        @endif
        @forelse (isset($rincianTransaksi) ? $rincianTransaksi : collect() as $detail)
            @php
                $t = $detail['transaksi'];
                $nominal = (int) $t->nominal;
                if($t->jenis_trans === 'setoran') {
                    $debit = $nominal; $kredit = 0; $saldoRunning += $nominal;
                } else {
                    $debit = 0; $kredit = $nominal; $saldoRunning -= $nominal;
                }
            @endphp
            <tr>
                <td class="text-center">{{ $detail['laporan']->tanggal->format('d/m/Y') }}</td>
                <td class="text-center">{{ $t->created_at?->format('H:i') ?? '-' }}</td>
                <td>{{ $t->no_struk }}</td>
                <td>{{ $detail['laporan']->teller?->nama_petugas ?? '-' }}</td>
                <td>{{ $t->nasabah?->no_rekening ?? '-' }}</td>
                <td>{{ $t->nasabah?->nama_siswa ?? '-' }}</td>
                <td class="text-center">{{ $t->jenis_label }}</td>
                <td class="text-end">{{ $debit ? number_format($debit, 0, ',', '.') : '-' }}</td>
                <td class="text-end">{{ $kredit ? number_format($kredit, 0, ',', '.') : '-' }}</td>
                <td class="text-end">{{ number_format($saldoRunning, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="empty">Tidak ada detail transaksi pada periode ini</td></tr>
        @endforelse
    </tbody>
    @if(isset($rincianTransaksi) && $rincianTransaksi->count() > 0)
    <tfoot>
        <tr>
            <td colspan="7" class="text-end fw-bold">TOTAL TRANSAKSI ({{ $rincianTransaksi->count() }})</td>
            <td class="text-end fw-bold">{{ number_format($rincianTransaksi->filter(fn($d)=>isset($d['transaksi']) && $d['transaksi']->jenis_trans==='setoran')->sum(fn($d)=>$d['transaksi']->nominal), 0, ',', '.') }}</td>
            <td class="text-end fw-bold">{{ number_format($rincianTransaksi->filter(fn($d)=>isset($d['transaksi']) && $d['transaksi']->jenis_trans==='penarikan')->sum(fn($d)=>$d['transaksi']->nominal), 0, ',', '.') }}</td>
            <td class="text-end fw-bold">{{ number_format($saldoRunning, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>
EOD;
if (strpos($c, $old) === false) { echo "NOT_FOUND\n"; exit; }
$c = str_replace($old, $new, $c);
file_put_contents($path, $c);
echo "OK\n";
