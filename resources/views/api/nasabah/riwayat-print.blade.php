<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cetak Mutasi Rekening — {{ $nasabah->no_rekening }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #111; margin: 0; padding: 18px; background: #f5f6f8; }
        .sheet { max-width: 900px; margin: 0 auto 16px; background: #fff; padding: 24px 28px; border: 1px solid #ddd; }
        .kop { text-align: center; border-bottom: 3px double #333; padding-bottom: 10px; margin-bottom: 14px; }
        .kop h1 { margin: 0; font-size: 20px; letter-spacing: 1px; }
        .kop .sub { font-size: 12px; color: #444; }
        .kop .alamat { font-size: 11px; color: #666; }
        h2.judul { text-align: center; font-size: 15px; text-transform: uppercase; margin: 4px 0 14px; text-decoration: underline; }
        .info-nasabah { display: flex; flex-wrap: wrap; gap: 6px 28px; margin-bottom: 12px; font-size: 12.5px; }
        .info-nasabah b { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #999; padding: 5px 7px; vertical-align: top; }
        th { background: #eef1f5; text-transform: uppercase; font-size: 11px; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .pos { color: #157347; }
        .neg { color: #b02a37; }
        .ringkas { margin-top: 14px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
        .ringkas .box { border: 1px solid #999; padding: 8px 14px; min-width: 200px; }
    .ttd { display: flex; justify-content: space-between; margin-top: 34px; text-align: center; font-size: 12.5px; display: none; }
    .ttd .box { width: 230px; }
    .ttd .nama { font-weight: 600; text-decoration: underline; }
        .aksi { max-width: 900px; margin: 0 auto 18px; display: flex; gap: 8px; }
        .aksi a, .aksi button { font-size: 13px; padding: 8px 16px; border-radius: 6px; border: 1px solid #333; cursor: pointer; text-decoration: none; color: #fff; background: #333; }
        .aksi .back { background: #fff; color: #333; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { border: none; margin: 0; padding: 0; max-width: none; page-break-after: always; }
            .aksi { display: none !important; }
        }
    </style>
</head>
<body>
<div class="aksi">
    <button type="button" onclick="window.print()">&#128424; Cetak</button>
</div>

<div class="sheet">
    <div class="kop">
        <h1>{{ \App\Support\InfoBank::NAMA }}</h1>
        <div class="sub">{{ \App\Support\InfoBank::LEMBAGA }} &middot; {{ \App\Support\InfoBank::TAGLINE }}</div>
        <div class="alamat">{{ \App\Support\InfoBank::ALAMAT }}</div>
    </div>

    <h2 class="judul">Buku Mutasi Simpanan Nasabah</h2>

    <div class="info-nasabah">
        <span>Nama: <b>{{ $nasabah->nama_siswa }}</b></span>
        <span>No. Rekening: <b>{{ $nasabah->no_rekening }}</b></span>
        <span>NIS: <b>{{ $nasabah->nis ?: '-' }}</b></span>
        <span>Kelas: <b>{{ $nasabah->kelas ?: '-' }}</b></span>
        <span>Produk: <b>{{ $nasabah->produk_label ?? '-' }}</b></span>
    </div>

    <div class="info-nasabah">
        <span>Filter: <b>
            {{ $request->jenis ? ucfirst($request->jenis) : 'Semua jenis' }} &middot;
            {{ $request->dari ?: 'dari awal' }} s/d {{ $request->sampai ?: 'hari ini' }}
        </b></span>
        <span>Dicetak: <b>{{ now()->translatedFormat('d F Y H:i') }} WIB</b></span>
        <span>Oleh: <b>{{ auth()->user()?->nama_petugas ?? '-' }}</b></span>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:34px;">No</th>
                <th>Tanggal</th>
                <th>No. Struk</th>
                <th>Keterangan</th>
                <th>Teller</th>
                <th class="text-end">Kredit (Rp)</th>
                <th class="text-end">Debit (Rp)</th>
                <th class="text-end">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalKredit = 0; $totalDebit = 0; @endphp
            @forelse ($baris as $i => $baris_)
                @php
                    $t = $baris_['transaksi'];
                    $kredit = $t->is_setoran ? (int) $t->nominal : 0;
                    $debit = $t->is_setoran ? 0 : (int) $t->nominal;
                    $totalKredit += $kredit;
                    $totalDebit += $debit;
                @endphp
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->tanggal)->translatedFormat('d/m/Y') }}</td>
                    <td>{{ $t->no_struk ?: '-' }}</td>
                    <td>
                        {{ $t->jenis_label }}
                        @if ($t->status !== 'approved')
                            <span class="neg">({{ $t->status_label }})</span>
                        @endif
                    </td>
                    <td>{{ $t->user?->nama_petugas ?? '-' }}</td>
                    <td class="text-end pos">{{ $kredit ? number_format($kredit, 0, ',', '.') : '-' }}</td>
                    <td class="text-end neg">{{ $debit ? number_format($debit, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ number_format($baris_['saldo_sesudah'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada transaksi pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="text-end">Total</th>
                <th class="text-end">{{ number_format($totalKredit, 0, ',', '.') }}</th>
                <th class="text-end">{{ number_format($totalDebit, 0, ',', '.') }}</th>
                <th class="text-end">{{ number_format((int) $nasabah->saldo, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="ringkas">
        <div class="box">
            <div>Jumlah mutasi: <b>{{ count($baris) }}</b> transaksi</div>
            <div>Saldo akhir: <b>Rp {{ number_format((int) $nasabah->saldo, 0, ',', '.') }}</b></div>
        </div>
        <div class="box">
            <div>Total Kredit: <b class="pos">Rp {{ number_format($totalKredit, 0, ',', '.') }}</b></div>
            <div>Total Debit: <b class="neg">Rp {{ number_format($totalDebit, 0, ',', '.') }}</b></div>
        </div>
    </div>

    <div class="ttd" style="display:none;">
        <div class="box">Nasabah,</div>
        <div class="box">
            <div>Petugas,</div>
            <div class="nama">{{ $nasabah->nama_siswa }}</div>
        </div>
    </div>
</div>
</body>
</html>
