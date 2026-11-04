@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'MUTASI SIMPANAN NASABAH',
    'periode' => isset($mulai) && isset($sampai) ? $mulai->format('d/m/Y').' s/d '.$sampai->format('d/m/Y') : date('d/m/Y'),
])

<h2>DATA NASABAH</h2>
<table style="width: 60%;">
    <tr>
        <td style="border: none; padding: 2px 4px;"><strong>Nama Nasabah</strong></td>
        <td style="border: none; padding: 2px 4px;">: {{ $nasabah->nama_siswa }}</td>
    </tr>
    <tr>
        <td style="border: none; padding: 2px 4px;"><strong>No. Rekening</strong></td>
        <td style="border: none; padding: 2px 4px;">: {{ $nasabah->no_rekening }}</td>
    </tr>
    <tr>
        <td style="border: none; padding: 2px 4px;"><strong>Produk Tabungan</strong></td>
        <td style="border: none; padding: 2px 4px;">: {{ $nasabah->produk_label }}</td>
    </tr>
    <tr>
        <td style="border: none; padding: 2px 4px;"><strong>Saldo Saat Ini</strong></td>
        <td style="border: none; padding: 2px 4px;">: Rp {{ number_format($nasabah->saldo, 0, ',', '.') }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th style="width: 12%;">Tanggal</th>
            <th style="width: 18%;">No. Struk</th>
            <th style="width: 18%;">Jenis Transaksi</th>
            <th class="text-end" style="width: 17%;">Debit (Setoran)</th>
            <th class="text-end" style="width: 17%;">Kredit (Penarikan)</th>
            <th class="text-end" style="width: 18%;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background-color: #f0f7ff;">
            <td colspan="5" class="fw-bold">SALDO AWAL</td>
            <td class="text-end fw-bold">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
        </tr>
        @forelse ($mutasi as $m)
            <tr>
                <td class="text-center">{{ $m['tanggal']->format('d/m/Y') }}</td>
                <td>{{ $m['no_struk'] }}</td>
                <td class="text-center">{{ $m['jenis'] }}</td>
                <td class="text-end">{{ $m['debit'] ? 'Rp '.number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                <td class="text-end">{{ $m['kredit'] ? 'Rp '.number_format($m['kredit'], 0, ',', '.') : '-' }}</td>
                <td class="text-end fw-bold">Rp {{ number_format($m['saldo'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="empty">Tidak ada data untuk periode ini</td>
            </tr>
        @endforelse
    </tbody>
    @if($mutasi->count() > 0)
    <tfoot>
        <tr>
            <td colspan="5" class="text-end fw-bold">SALDO AKHIR</td>
            <td class="text-end fw-bold">Rp {{ number_format($nasabah->saldo, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">Dicetak melalui Sistem Bank Mini Sekolah | Halaman</div>
