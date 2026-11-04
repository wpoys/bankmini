@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'LAPORAN TAHUNAN',
    'periode' => 'Tahun '.$tahun,
])

<h2>RINGKASAN TAHUN {{ $tahun }}</h2>
<table class="ringkasan" style="width: 50%;">
    <thead>
        <tr>
            <th colspan="2" style="text-align: center;">RINGKASAN</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>Total Setoran</td><td class="text-end">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td></tr>
        <tr><td>Total Penarikan</td><td class="text-end">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td></tr>
        <tr><td>Jumlah Transaksi</td><td class="text-end">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</td></tr>
        <tr><td>Nasabah Baru</td><td class="text-end">{{ number_format($nasabahBaru, 0, ',', '.') }}</td></tr>
        <tr><td>Saldo Akhir Total</td><td class="text-end">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</td></tr>
        <tr style="background-color: #e6f0ff;"><td class="fw-bold">Selisih (Setoran - Penarikan)</td><td class="text-end fw-bold">Rp {{ number_format($totalSetoran - $totalPenarikan, 0, ',', '.') }}</td></tr>
    </tbody>
</table>

<h2 style="margin-top: 16px;">PERKEMBANGAN TRANSAKSI BULANAN</h2>
<table>
    <thead>
        <tr>
            <th style="width: 30%;">Bulan</th>
            <th class="text-end" style="width: 23%;">Total Setoran</th>
            <th class="text-end" style="width: 23%;">Total Penarikan</th>
            <th class="text-end" style="width: 24%;">Selisih</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($perBulan as $b)
            <tr>
                <td>{{ $b['bulan'] }}</td>
                <td class="text-end">Rp {{ number_format($b['setoran'], 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($b['penarikan'], 0, ',', '.') }}</td>
                <td class="text-end">Rp {{ number_format($b['setoran'] - $b['penarikan'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="text-end fw-bold">TOTAL TAHUN {{ $tahun }}</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalSetoran, 0, ',', '.') }}</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalPenarikan, 0, ',', '.') }}</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalSetoran - $totalPenarikan, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Dicetak melalui Sistem Bank Mini Sekolah | Halaman</div>
