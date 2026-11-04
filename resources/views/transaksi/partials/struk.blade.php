{{--
    Struk 58mm — dipakai bersama oleh:
      1. resources/views/transaksi/struk.blade.php      (modal pratinjau di layar)
      2. resources/views/transaksi/struk-print.blade.php (blade khusus cetak)
      3. resources/views/transaksi/struk-pdf.blade.php   (DomPDF)

    Variabel: $transaksi, $nasabah, $teller, $nominal, $saldoSebelum,
              $saldoSesudah, $catatan, $bank, $logo, $qrSvg, $qrImg, $dicetakOleh
--}}
@php
    $tanggalStruk = $transaksi->tanggal_transaksi;
    $jamStruk = $transaksi->jam_transaksi;
    $waktuCetak = \Carbon\Carbon::now()->translatedFormat('d/m/Y H:i');
@endphp
<div class="r-struk">
    <div class="r-kop">
        @if (! empty($logo))
            <img src="{{ $logo }}" alt="Logo {{ $bank['nama'] }}" class="r-logo">
        @else
            <div class="r-logo-teks">BM</div>
        @endif
        <div class="r-nama">{{ $bank['nama'] }}</div>
        <div class="r-sub">{{ $bank['lembaga'] }}</div>
        <div class="r-alamat">{{ $bank['alamat'] }}</div>
        <div class="r-tagline">{{ $bank['tagline'] }}</div>
    </div>

    <div class="r-sep"></div>
    <div class="r-judul">BUKTI TRANSAKSI</div>
    <div class="r-sep"></div>

    <table class="r-table">
        <tr>
            <td class="r-k">No. Struk</td>
            <td class="r-v"><strong>{{ $transaksi->no_struk ?: '-' }}</strong></td>
        </tr>
        <tr>
            <td class="r-k">Tanggal</td>
            <td class="r-v">{{ $tanggalStruk }}</td>
        </tr>
        <tr>
            <td class="r-k">Waktu</td>
            <td class="r-v">{{ $jamStruk }} WIB</td>
        </tr>
    </table>

    <div class="r-sep"></div>
    <div class="r-kop-2">DATA NASABAH</div>

    <table class="r-table">
        <tr>
            <td class="r-k">Nama</td>
            <td class="r-v">{{ $nasabah?->nama_siswa ?? '-' }}</td>
        </tr>
        <tr>
            <td class="r-k">Rekening</td>
            <td class="r-v">{{ $nasabah?->no_rekening ?? '-' }}</td>
        </tr>
        <tr>
            <td class="r-k">NIS</td>
            <td class="r-v">{{ $nasabah?->nis ?? '-' }}</td>
        </tr>
        <tr>
            <td class="r-k">Kelas</td>
            <td class="r-v">{{ $nasabah?->kelas ?: '-' }}</td>
        </tr>
        <tr>
            <td class="r-k">Produk</td>
            <td class="r-v">{{ $nasabah?->produk_label ?? '-' }}</td>
        </tr>
    </table>

    <div class="r-sep"></div>

    <table class="r-table">
        <tr>
            <td class="r-k">Jenis</td>
            <td class="r-v"><strong>{{ strtoupper($transaksi->jenis_label) }}</strong></td>
        </tr>
        <tr>
            <td class="r-k">Status</td>
            <td class="r-v">{{ strtoupper($transaksi->status_label) }}</td>
        </tr>
    </table>

    <div class="r-box">
        <div class="r-box-label">NOMINAL {{ strtoupper($transaksi->jenis_label) }}</div>
        <div class="r-box-nilai">{{ $transaksi->nominal_rupiah }}</div>
        <div class="r-box-label">{{ $transaksi->is_setoran ? 'Tanda (+) Setoran' : 'Tanda (-) Penarikan' }}</div>
        <div class="r-box-label" style="text-transform:uppercase;">Terbilang: {{ \App\Support\Terbilang::rupiah((int) $transaksi->nominal) }}</div>
    </div>

    <table class="r-table">
        <tr>
            <td class="r-k">Saldo Sebelum</td>
            <td class="r-v">Rp {{ number_format($saldoSebelum, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="r-k">Saldo Sesudah</td>
            <td class="r-v"><strong>Rp {{ number_format($saldoSesudah, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    <div class="r-sep"></div>

    <table class="r-table">
        <tr>
            <td class="r-k">Teller</td>
            <td class="r-v">{{ $teller }}</td>
        </tr>
        @if (! empty($catatan))
            <tr>
                <td class="r-k">Catatan</td>
                <td class="r-v">{{ $catatan }}</td>
            </tr>
        @else
            <tr>
                <td class="r-k">Catatan</td>
                <td class="r-v">-</td>
            </tr>
        @endif
    </table>

    <div class="r-qr-area">
        @if (! empty($qrImg))
            <img src="{{ $qrImg }}" alt="QR Code struk {{ $transaksi->no_struk }}" class="r-qr">
        @elseif (! empty($qrSvg))
            <div class="r-qr">{!! $qrSvg !!}</div>
        @endif
        <div class="r-qr-teks">No. {{ $transaksi->no_struk ?: '-' }}</div>
        <div class="r-qr-teks">{{ $tanggalStruk }} {{ $jamStruk }}</div>
    </div>

    <div class="r-sep"></div>

    <div class="r-footer-teks">
        <div>{{ $bank['keterangan'] }}</div>
        <div>Simpan struk ini sebagai bukti transaksi yang sah.</div>
        <div>Dicetak oleh {{ $dicetakOleh }} &middot; {{ $waktuCetak }}</div>
        <div>Terima kasih atas kunjungan Anda.</div>
    </div>
</div>
