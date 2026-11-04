@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'BUKU BESAR',
    'periode' => $mulai->format('d/m/Y').' s/d '.$sampai->format('d/m/Y'),
])

<table>
    <thead>
        <tr>
            <th style="width: 10%;">Tanggal</th>
            <th style="width: 12%;">No. Struk</th>
            <th style="width: 20%;">Nama Nasabah</th>
            <th style="width: 14%;">No. Rekening</th>
            <th style="width: 10%;">Jenis</th>
            <th class="text-end" style="width: 11%;">Debit (Rp)</th>
            <th class="text-end" style="width: 11%;">Kredit (Rp)</th>
            <th class="text-end" style="width: 12%;">Saldo (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background-color: #f0f7ff;">
            <td colspan="7" class="fw-bold">SALDO AWAL</td>
            <td class="text-end fw-bold">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
        </tr>
        @forelse ($mutasi as $m)
            <tr>
                <td class="text-center">{{ $m['tanggal']->format('d/m/Y') }}</td>
                <td>{{ $m['no_struk'] }}</td>
                <td>{{ $m['nasabah'] }}</td>
                <td>{{ $m['rekening'] }}</td>
                <td class="text-center">{{ $m['jenis'] }}</td>
                <td class="text-end">{{ $m['debit'] ? 'Rp '.number_format($m['debit'], 0, ',', '.') : '-' }}</td>
                <td class="text-end">{{ $m['kredit'] ? 'Rp '.number_format($m['kredit'], 0, ',', '.') : '-' }}</td>
                <td class="text-end fw-bold">Rp {{ number_format($m['saldo'], 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="empty">Tidak ada data untuk periode ini</td>
            </tr>
        @endforelse
    </tbody>
    @if($mutasi->count() > 0)
    <tfoot>
        <tr>
            <td colspan="7" class="text-end fw-bold">SALDO AKHIR</td>
            <td class="text-end fw-bold">Rp {{ number_format($mutasi->last()['saldo'] ?? $saldoAwal, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="footer">Dicetak melalui Sistem Bank Mini Sekolah | Halaman</div>
