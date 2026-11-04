@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'JURNAL AKUNTANSI',
    'periode' => $mulai->format('d/m/Y').' s/d '.$sampai->format('d/m/Y').(isset($tellerLabel) && $tellerLabel ? ' — Teller: '.$tellerLabel : ''),
])

<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Keterangan / Nasabah</th>
            <th>Produk</th>
            <th>No. Struk</th>
            <th>Kode Akun</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Kredit</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($jurnal as $j)
            <tr>
                <td>{{ $j->transaksi?->tanggal?->format('d/m/Y') ?? $j->created_at->format('d/m/Y') }}</td>
                <td>{{ $j->transaksi?->nasabah?->nama_siswa ?? 'Non-transaksi' }}</td>
                <td>{{ $j->transaksi?->nasabah?->produk_label ?? '-' }}</td>
                <td>{{ $j->transaksi?->no_struk ?? '-' }}</td>
                <td>{{ $j->kode_akun }}</td>
                <td class="text-end">{{ $j->posisi === 'debit' ? number_format($j->jumlah, 0, ',', '.') : '-' }}</td>
                <td class="text-end">{{ $j->posisi === 'kredit' ? number_format($j->jumlah, 0, ',', '.') : '-' }}</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="fw-bold">
            <td colspan="5" class="text-end">TOTAL</td>
            <td class="text-end">{{ number_format($totalDebit, 0, ',', '.') }}</td>
            <td class="text-end">{{ number_format($totalKredit, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td colspan="7" class="text-center">Debit &equiv; Kredit (seimbang): {{ $totalDebit === $totalKredit ? 'Ya' : 'TIDAK' }}</td>
        </tr>
    </tfoot>
</table>

<div class="footer">Dicetak melalui Sistem Bank Mini Sekolah | Halaman</div>
