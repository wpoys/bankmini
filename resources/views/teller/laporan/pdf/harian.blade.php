@include('supervisor.laporan.pdf.kepala', [
    'judul' => 'LAPORAN HARIAN TELLER / PENUTUPAN KAS',
    'periode' => $laporan->tanggal->format('d/m/Y'),
    'tanggalCetak' => now()->format('d/m/Y H:i').' WIB',
])

<div class="section-title">IDENTITAS LAPORAN</div>
<table class="info-table" style="width: 100%;">
    <tr>
        <td style="width: 25%;">Tanggal Laporan</td>
        <td style="width: 2%;">:</td>
        <td style="width: 73%;">{{ $laporan->tanggal->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td>Nama Teller</td>
        <td>:</td>
        <td>{{ $laporan->teller->nama_petugas }}</td>
    </tr>
    <tr>
        <td>Status Laporan</td>
        <td>:</td>
        <td>{{ $laporan->status_label }}</td>
    </tr>
    @if($laporan->submitted_at)
    <tr>
        <td>Dikirim</td>
        <td>:</td>
        <td>{{ $laporan->submitted_at->format('d/m/Y H:i') }} WIB</td>
    </tr>
    @endif
    @if($laporan->approved_at)
    <tr>
        <td>Waktu Verifikasi</td>
        <td>:</td>
        <td>{{ $laporan->approved_at->format('d/m/Y H:i') }} WIB</td>
    </tr>
    @endif
    @if($laporan->approver)
    <tr>
        <td>Verifikator</td>
        <td>:</td>
        <td>{{ $laporan->approver->nama_petugas }}</td>
    </tr>
    @endif
</table>

<div class="section-title">REKAPITULASI KAS</div>
<table>
    <thead>
        <tr>
            <th style="width: 60%;">Keterangan</th>
            <th class="text-end" style="width: 40%;">Nilai (Rp)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Saldo Awal Kas</td>
            <td class="text-end">{{ number_format($laporan->saldo_awal_kas, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Setoran ({{ $laporan->detailLaporan->where('transaksi.jenis_trans', 'setoran')->count() }} transaksi)</td>
            <td class="text-end">{{ number_format($laporan->total_setoran, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Total Penarikan ({{ $laporan->detailLaporan->where('transaksi.jenis_trans', 'penarikan')->count() }} transaksi)</td>
            <td class="text-end">{{ number_format($laporan->total_penarikan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="fw-bold">Saldo Akhir Sistem</td>
            <td class="text-end fw-bold">{{ number_format($laporan->saldo_akhir_sistem, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Uang Fisik di Laci</td>
            <td class="text-end">{{ $laporan->saldo_akhir_fisik !== null ? number_format($laporan->saldo_akhir_fisik, 0, ',', '.') : '-' }}</td>
        </tr>
        <tr>
            <td>Selisih (Fisik - Sistem)</td>
            <td class="text-end" style="color: {{ ($laporan->selisih == 0) ? '#198754' : (($laporan->selisih < 0) ? '#dc3545' : '#b02a37') }};">{{ $laporan->selisih !== null ? number_format($laporan->selisih, 0, ',', '.') : '-' }}</td>
        </tr>
        @if($laporan->kas_pindah_brankas)
        <tr>
            <td>Kas Dipindahkan ke Brankas</td>
            <td class="text-end">{{ number_format($laporan->kas_dipindahkan, 0, ',', '.') }}</td>
        </tr>
        @endif
    </tbody>
</table>

@if(class_exists(\App\Support\Terbilang::class))
<div style="margin-top:4px; font-size:9px;"><strong>Terbilang Saldo Akhir Sistem:</strong> {{ strtoupper(\App\Support\Terbilang::rupiah((int)$laporan->saldo_akhir_sistem)) }}</div>
@endif

<div class="section-title">RINCIAN LEMBARAN SALDO AWAL</div>
<table>
    <thead>
        <tr>
            <th class="text-center">Nominal (Rp)</th>
            <th class="text-center" style="width: 25%;">Jumlah Lembar</th>
            <th class="text-end" style="width: 35%;">Total (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $sisaSaldoAwal = (int) $laporan->saldo_awal_kas;
            $pecahanSaldoAwal = [100000, 50000, 20000, 10000, 5000, 2000, 1000, 500, 100];
        @endphp
        @foreach($pecahanSaldoAwal as $pecahan)
            @php
                $jumlahLembarAwal = intdiv($sisaSaldoAwal, $pecahan);
                $subtotalAwal = $pecahan * $jumlahLembarAwal;
                $sisaSaldoAwal -= $subtotalAwal;
            @endphp
            @if($jumlahLembarAwal > 0)
                <tr>
                    <td class="text-center">Rp {{ number_format($pecahan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $jumlahLembarAwal }} lembar</td>
                    <td class="text-end">Rp {{ number_format($subtotalAwal, 0, ',', '.') }}</td>
                </tr>
            @endif
        @endforeach
        @if((int) $laporan->saldo_awal_kas === 0)
            <tr><td colspan="3" class="empty">Tidak ada saldo awal</td></tr>
        @endif
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="text-end fw-bold">TOTAL SALDO AWAL</td>
            <td class="text-end fw-bold">Rp {{ number_format($laporan->saldo_awal_kas, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<div class="section-title">RINCIAN LEMBARAN UANG FISIK</div>
<table>
    <thead>
        <tr>
            <th class="text-center">Nominal (Rp)</th>
            <th class="text-center" style="width: 25%;">Jumlah Lembar</th>
            <th class="text-end" style="width: 35%;">Total (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @php $totalDenom = 0; @endphp
        @if($laporan->rekonsiliasi && $laporan->rekonsiliasi->detail && $laporan->rekonsiliasi->detail->count() > 0)
            @foreach($laporan->rekonsiliasi->detail as $d)
                @php $sub = $d->pecahan * $d->jumlah_lembar; $totalDenom += $sub; @endphp
                <tr>
                        <td class="text-center">Rp {{ number_format($d->pecahan, 0, ',', '.') }}</td>
                        <td class="text-center">{{ $d->jumlah_lembar }} lembar</td>
                        <td class="text-end">Rp {{ number_format($sub, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        @else
            <tr>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-end">Rp {{ number_format($laporan->saldo_akhir_fisik ?? 0, 0, ',', '.') }}</td>
            </tr>
        @endif
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2" class="text-end fw-bold">TOTAL UANG FISIK DI LACI</td>
            <td class="text-end fw-bold">Rp {{ number_format($laporan->saldo_akhir_fisik ?? $totalDenom, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<div class="section-title">RINCIAN TRANSAKSI</div>
<table>
    <thead>
        <tr>
            <th style="width: 9%;">Waktu</th>
            <th style="width: 16%;">Nomor Transaksi</th>
            <th style="width: 12%;">Jenis Transaksi</th>
            <th style="width: 16%;">Nomor Rekening</th>
            <th style="width: 27%;">Nama Nasabah</th>
            <th class="text-end" style="width: 20%;">Nominal (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($laporan->detailLaporan as $detail)
            @php $t = $detail->transaksi; @endphp
            <tr>
                <td class="text-center">{{ $t->created_at->format('H:i') }}</td>
                <td>{{ $t->no_struk }}</td>
                <td class="text-center">{{ $t->jenis_label }}</td>
                <td>{{ $t->nasabah->no_rekening ?? '-' }}</td>
                <td>{{ $t->nasabah->nama_siswa ?? '-' }}</td>
                <td class="text-end">{{ number_format($t->nominal, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="empty">Tidak ada detail transaksi</td>
            </tr>
        @endforelse
    </tbody>
    @if($laporan->detailLaporan->count() > 0)
    <tfoot>
        <tr>
            <td colspan="6" class="text-center fw-bold">Jumlah Transaksi: {{ $laporan->detailLaporan->count() }} | Total Setoran: Rp {{ number_format($laporan->total_setoran, 0, ',', '.') }} | Total Penarikan: Rp {{ number_format($laporan->total_penarikan, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
    @endif
</table>

<div class="section-title">KETERANGAN VERIFIKASI</div>
<table class="info-table" style="width: 100%;">
    <tr>
        <td style="width: 25%;">Status Verifikasi</td>
        <td style="width: 2%;">:</td>
        <td style="width: 73%;">{{ $laporan->status_label }}</td>
    </tr>
    @if($laporan->approver)
    <tr>
        <td>Nama / Peran Verifikator</td>
        <td>:</td>
        <td>{{ $laporan->approver->nama_petugas }} (Supervisor)</td>
    </tr>
    @endif
    @if($laporan->approved_at)
    <tr>
        <td>Tanggal & Waktu Verifikasi</td>
        <td>:</td>
        <td>{{ $laporan->approved_at->format('d/m/Y H:i') }} WIB</td>
    </tr>
    @endif
    @if($laporan->catatan_supervisor)
    <tr>
        <td>Catatan Verifikasi</td>
        <td>:</td>
        <td>{{ $laporan->catatan_supervisor }}</td>
    </tr>
    @endif
</table>

<div class="footer">Dicetak pada {{ now()->format('d/m/Y H:i') }} WIB melalui Sistem Bank Mini Sekolah | Halaman</div>
