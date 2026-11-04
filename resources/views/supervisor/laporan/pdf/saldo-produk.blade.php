@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'REKAPITULASI SALDO BERDASARKAN PRODUK',
    'periode' => 'Per Tanggal '.$tanggal->format('d/m/Y'),
])

<table>
    <thead>
        <tr>
            <th style="width: 35%;">Produk Tabungan</th>
            <th class="text-end" style="width: 20%;">Jumlah Nasabah</th>
            <th class="text-end" style="width: 25%;">Total Saldo</th>
            <th class="text-end" style="width: 20%;">Rata-rata Saldo</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ringkasan as $r)
            @if (isset($r['produk']))
                <tr>
                    <td>{{ $r['produk_label'] }}</td>
                    <td class="text-end">{{ number_format($r['jumlah_nasabah'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($r['total_saldo'], 0, ',', '.') }}</td>
                    <td class="text-end">Rp {{ number_format($r['jumlah_nasabah'] > 0 ? intval($r['total_saldo'] / $r['jumlah_nasabah']) : 0, 0, ',', '.') }}</td>
                </tr>
            @endif
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td class="text-end fw-bold">JUMLAH TOTAL</td>
            <td class="text-end fw-bold">{{ number_format($totalNasabah, 0, ',', '.') }}</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalSeluruh, 0, ',', '.') }}</td>
            <td class="text-end fw-bold">Rp {{ number_format($totalNasabah > 0 ? intval($totalSeluruh / $totalNasabah) : 0, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Dicetak melalui Sistem Bank Mini Sekolah | Halaman</div>
