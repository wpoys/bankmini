{{--
    CSS bersama untuk struk 58mm.
    Variabel: $ukuran (font-size dasar), mis. '11px' (layar/cetak) atau '7.6pt' (PDF).
--}}
<style>
    .r-struk {
        font-family: 'Courier New', ui-monospace, 'DejaVu Sans Mono', monospace;
        font-size: {{ $ukuran ?? '11px' }};
        line-height: 1.35;
        color: #111;
        width: 100%;
        margin: 0 auto;
    }
    .r-struk * { box-sizing: border-box; }
    .r-kop { text-align: center; }
    .r-logo { display: block; width: 34px; height: 34px; margin: 0 auto 3px; }
    .r-logo-teks {
        width: 34px; height: 32px; line-height: 30px; margin: 0 auto 3px;
        border: 1.4px solid #111; border-radius: 50%;
        font-weight: bold; text-align: center; font-size: 12px;
    }
    .r-nama { font-weight: bold; font-size: 1.14em; text-transform: uppercase; letter-spacing: 0.4px; }
    .r-sub { font-size: 0.78em; }
    .r-alamat { font-size: 0.7em; }
    .r-tagline { font-size: 0.7em; letter-spacing: 1px; }
    .r-sep { border-top: 1px dashed #111; margin: 4px 0; }
    .r-judul { text-align: center; font-weight: bold; letter-spacing: 1px; }
    .r-kop-2 { text-align: center; font-weight: bold; font-size: 0.85em; letter-spacing: 0.5px; }
    .r-table { width: 100%; border-collapse: collapse; }
    .r-table td { padding: 0.5px 0; vertical-align: top; font-size: 0.94em; }
    .r-k { width: 36%; }
    .r-v { text-align: right; word-wrap: break-word; }
    .r-box { border: 1px solid #111; padding: 4px 2px; margin: 5px 0; text-align: center; }
    .r-box-label { font-size: 0.7em; letter-spacing: 0.4px; }
    .r-box-nilai { font-weight: bold; font-size: 1.3em; margin: 1px 0; }
    .r-qr-area { text-align: center; margin-top: 6px; }
    .r-qr { display: block; width: 100%; text-align: center; }
    .r-qr img, .r-qr svg { display: block; width: 94px; height: 94px; margin: 0 auto; }
    .r-qr-teks { font-size: 0.68em; text-align: center; }
    .r-footer-teks { text-align: center; font-size: 0.68em; line-height: 1.35; }
</style>
