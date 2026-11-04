<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $transaksi->no_struk }} — {{ $bank['nama'] }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        @page {
            size: 58mm {{ \App\Http\Controllers\StrukController::TINGGI_KERTAS_MM }}mm;
            margin: 3mm;
        }

        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }
        body {
            font-family: 'Courier New', ui-monospace, monospace;
            font-size: 9.4px;
        }
        .struk-58 { width: 100%; max-width: 100%; margin: 0 auto; }
        .cetak-aksi {
            max-width: 320px;
            margin: 0 auto 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: center;
        }
        .cetak-aksi a, .cetak-aksi button {
            font: inherit;
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #999;
            background: #fff;
            color: #16233B;
            cursor: pointer;
            text-decoration: none;
        }
        .cetak-aksi .utama { background: #1D4ED8; border-color: #1D4ED8; color: #fff; }

        @media print {
            .cetak-aksi { display: none !important; }
            .struk-58 { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="cetak-aksi">
        <button type="button" class="utama" onclick="window.print()">Cetak Struk</button>
        <a href="{{ $transaksi->struk_pdf_url }}">Download PDF</a>
        <button type="button" onclick="window.close()">Tutup</button>
    </div>

    <div class="struk-58">
        @include('transaksi.partials.struk-css', ['ukuran' => '9.4px'])
        @include('transaksi.partials.struk')
    </div>

    <script>
        // Jalankan dialog cetak otomatis ketika halaman dibuka dari tombol "Cetak Struk".
        (function () {
            const otomatis = new URLSearchParams(window.location.search).get('cetak') === '1';
            if (! otomatis) return;
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 250);
            });
        })();
    </script>
</body>
</html>
