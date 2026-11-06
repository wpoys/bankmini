<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaksi->no_struk }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @include('transaksi.partials.struk-css', ['ukuran' => '12px'])
    <style>
        body { margin: 0; padding: 16px; background: #f3f5fa; font-family: 'Plus Jakarta Sans', 'Inter', sans-serif; }
        .struk-modal-card { width: min(420px, 100%); margin: 0 auto; overflow: hidden; border: 1px solid #dfe5ef; border-radius: 12px; background: #fff; box-shadow: 0 4px 14px rgba(18, 35, 67, .07); }
        .struk-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 12px 14px; border-bottom: 1px solid #dfe5ef; color: #17243e; font-size: 14px; font-weight: 800; }
        .struk-modal-body { padding: 14px; }
        .struk-modal-paper { width: min(300px, 100%); margin: 0 auto; padding: 14px; border: 1px dashed #cbd4e2; border-radius: 6px; background: #fff; }
        .struk-modal-actions { display: flex; justify-content: flex-end; gap: 8px; padding: 10px 14px; border-top: 1px solid #dfe5ef; }
    </style>
</head>
<body>
    <section class="struk-modal-card">
        <header class="struk-modal-head">
            <span><i class="fa-solid fa-receipt me-2 text-primary"></i>Struk {{ $transaksi->no_struk }}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.parent.postMessage('tutup-struk-modal', window.location.origin)" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        </header>
        <div class="struk-modal-body">
            <div class="struk-modal-paper">
                @include('transaksi.partials.struk-css', ['ukuran' => '12px'])
                @include('transaksi.partials.struk')
            </div>
        </div>
        <footer class="struk-modal-actions">
            <a href="{{ $transaksi->struk_pdf_url }}" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file-pdf me-1"></i>Unduh PDF</a>
            <a href="{{ $transaksi->struk_cetak_url }}?cetak=1" target="_blank" rel="noopener" class="btn btn-sm btn-primary"><i class="fa-solid fa-print me-1"></i>Cetak</a>
        </footer>
    </section>
</body>
</html>
