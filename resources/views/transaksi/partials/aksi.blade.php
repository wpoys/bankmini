{{--
    Tombol aksi struk untuk tabel transaksi.
    Hanya dirender bila pengguna berwenang atas transaksi tersebut
    (dicek lewat App\Policies\TransaksiPolicy).

    Pemakaian: @include('transaksi.partials.aksi', ['transaksi' => $t])
--}}
@php
    $strukLihat = $transaksi->struk_url;
    $strukCetak = $transaksi->boleh_cetak_struk ? $transaksi->struk_cetak_url : null;
    $strukPdf = $transaksi->boleh_unduh_struk ? $transaksi->struk_pdf_url : null;
@endphp
@if ($strukLihat)
    <div class="d-flex gap-1 justify-content-end text-nowrap">
        <a href="{{ $strukLihat }}" class="btn btn-sm btn-outline-primary" title="Lihat struk {{ $transaksi->no_struk }}">
            <i class="fa-solid fa-receipt me-1"></i>Lihat Struk
        </a>
        @if ($strukCetak)
            <a href="{{ $strukCetak }}?cetak=1" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"
               title="Cetak struk {{ $transaksi->no_struk }}" aria-label="Cetak struk {{ $transaksi->no_struk }}">
                <i class="fa-solid fa-print"></i>
            </a>
        @endif
        @if ($strukPdf)
            <a href="{{ $strukPdf }}" class="btn btn-sm btn-outline-secondary"
               title="Download PDF struk {{ $transaksi->no_struk }}" aria-label="Download PDF struk {{ $transaksi->no_struk }}">
                <i class="fa-solid fa-file-pdf"></i>
            </a>
        @endif
    </div>
@endif
