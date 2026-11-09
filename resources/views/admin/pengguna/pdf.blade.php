<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Daftar Pengguna Terpilih</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { font-size: 16px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px; text-align: left; }
        th { background: #e9eef5; }
    </style>
</head>
<body>
    <h1>Daftar Pengguna Terpilih</h1>
    <table>
        <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>@foreach ($row as $value)<td>{{ is_numeric($value) && count($row) > 8 ? number_format((float) $value, 0, ',', '.') : ($value ?? '—') }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
