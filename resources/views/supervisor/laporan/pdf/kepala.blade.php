<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { font-family: 'Helvetica', 'Arial', sans-serif; }
        body { font-size: 10px; color: #222; margin: 0; padding: 0; }
        .kop { text-align: center; border-bottom: 2px solid #0a2540; padding-bottom: 6px; margin-bottom: 10px; }
        .kop h1 { margin: 0 0 2px; font-size: 16px; color: #0a2540; letter-spacing: 1px; }
        .kop .sub { font-size: 9px; color: #555; }
        .kop .judul { font-size: 13px; font-weight: bold; margin-top: 4px; text-transform: uppercase; }
        h2 { font-size: 11px; margin: 10px 0 4px; font-weight: bold; }
        h3 { font-size: 10px; margin: 8px 0 4px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-inside: auto; }
        thead { display: table-header-group; }
        tfoot { display: table-row-group; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th { background: #f2f6fb; border: 1px solid #777; padding: 4px 5px; text-align: left; font-size: 9px; font-weight: bold; }
        td { border: 1px solid #777; padding: 3px 5px; vertical-align: top; word-wrap: break-word; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .fw-bold { font-weight: bold; }
        .ringkasan td, .ringkasan th { border: none; padding: 2px 4px; }
        .info-table td, .info-table th { border: none; padding: 2px 4px; }
        .footer { position: fixed; bottom: -12px; left: 0; right: 0; font-size: 8px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 2px; }
        .page-break { page-break-after: always; }
        .section-title { font-size: 10px; font-weight: bold; margin: 10px 0 4px; text-transform: uppercase; page-break-after: avoid; }
        .empty { text-align: center; color: #666; font-style: italic; }
        .catatan { font-size: 8px; color: #555; margin: 2px 0 5px; line-height: 1.35; }
        .fisik-block { page-break-inside: avoid; margin-bottom: 8px; }
        .fisik-head { font-size: 9px; font-weight: bold; background: #eef3f9; border: 1px solid #777; border-bottom: none; padding: 3px 5px; }
        .fisik-head .meta { font-weight: normal; color: #555; }
        .fisik-block table { margin-top: 0; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>BANK MINI SEKOLAH</h1>
        <div class="sub">Mudah, Aman, Terpercaya &mdash; Laporan Administrasi Tabungan Sekolah</div>
        <div class="judul">{{ $judul }}</div>
        @isset($periode)<div class="sub" style="font-size:9px; margin-top:2px;">Periode: {{ $periode }}</div>@endisset
        @isset($tanggalCetak)<div class="sub" style="font-size:8px;">Tanggal Cetak: {{ $tanggalCetak }}</div>@endisset
    </div>
