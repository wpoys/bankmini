<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk {{ $transaksi->no_struk }}</title>
    <style>
        @page {
            margin: 0;
        }

        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }
        body {
            font-family: 'Courier New', 'DejaVu Sans Mono', monospace;
            font-size: 7.6pt;
            width: {{ \App\Http\Controllers\StrukController::LEBAR_KERTAS_MM - 8 }}mm;
            padding: 4mm;
        }
    </style>
    @include('transaksi.partials.struk-css', ['ukuran' => '7.6pt'])
</head>
<body>
    @include('transaksi.partials.struk')
</body>
</html>
